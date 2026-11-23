<?php

namespace App\Services\Signage;

use App\Models\SignageMedia;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class SignageExport
{
    private const RETENTION = 86400;

    public function start(int $playlistId, int $ownerId): array
    {
        $admission = $this->lock('admission', true);
        try {
            $active = $this->read($this->root().'/active.json');
            if ($active && ($job = $this->read($this->directory($active['id']).'/job.json'))) {
                $job = $this->refresh($job);
                abort_if(in_array($job['status'], ['pending', 'running'], true), 409,
                    'Most egy másik videó készül. Próbáld újra az elkészülése után.');
            }
            $renderLock = $this->lock('render', false);
            abort_unless($renderLock, 409, 'Most egy másik videó készül. Próbáld újra később.');
            $this->unlock($renderLock);
            $this->cleanup();
            $runtime = $this->runtime();
            $snapshot = app(SignagePublisher::class)->build($playlistId);
            $seconds = array_sum(array_map(fn ($scene) => max($scene['duration'], $scene['media']['duration'] ?? 0), $snapshot['scenes']));
            if ($seconds <= 0 || $seconds > 1800) throw ValidationException::withMessages(['export'=>'Legfeljebb 30 perces műsorkörből készíthető videó.']);
            $id = (string) Str::uuid();
            $directory = $this->directory($id);
            File::ensureDirectoryExists($directory, 0700);
            $job = ['id'=>$id, 'owner_id'=>$ownerId, 'playlist_id'=>$playlistId, 'status'=>'pending',
                'created_at'=>microtime(true), 'deadline'=>time() + min(7200, 180 + 3 * $seconds + 30 * count($snapshot['scenes'])),
                'total_scenes'=>count($snapshot['scenes']),
                'filename'=>(Str::slug($snapshot['name']) ?: 'gyros-city').'-'.now()->format('Ymd-His').'.mp4'];
            $this->write($directory.'/input.json', $this->payload($snapshot, $runtime));
            $this->write($directory.'/job.json', $job);
            $this->write($this->root().'/active.json', ['id'=>$id]);
            try {
                $this->launch($id, $directory);
            } catch (\Throwable $e) {
                $job['status'] = 'failed';
                $this->write($directory.'/job.json', $job);
                logger()->warning('Signage export launch failed', ['job'=>$id, 'exception'=>get_class($e)]);
                throw ValidationException::withMessages(['export'=>'A videókészítés nem indult el. Próbáld újra később.']);
            }
            return $this->publicState($job);
        } finally { $this->unlock($admission); }
    }

    public function status(string $id, int $ownerId): array
    {
        return $this->publicState($this->owned($id, $ownerId));
    }

    public function latest(int $playlistId, int $ownerId): ?array
    {
        $jobs = [];
        foreach (glob($this->root().'/*/job.json') ?: [] as $file) {
            $job = $this->read($file);
            if ($job && $job['owner_id'] === $ownerId && $job['playlist_id'] === $playlistId && $job['created_at'] > time() - self::RETENTION) $jobs[] = $job;
        }
        usort($jobs, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);
        return $jobs ? $this->status($jobs[0]['id'], $ownerId) : null;
    }

    public function downloadPath(string $id, int $ownerId): string
    {
        $job = $this->owned($id, $ownerId);
        $path = $this->directory($id).'/output.mp4';
        abort_unless($job['status'] === 'complete' && is_file($path) && filesize($path) > 0, 409, 'A videó még nem tölthető le.');
        return $path;
    }

    public function run(string $id): bool
    {
        $directory = $this->directory($id);
        $admission = $this->lock('admission', true);
        $lock = null;
        try {
            $job = $this->read($directory.'/job.json');
            $active = $this->read($this->root().'/active.json');
            if (!$job || ($active['id'] ?? null) !== $id || $job['status'] !== 'pending' || $job['created_at'] < time() - 60) return false;
            $lock = $this->lock('render', false);
            if (!$lock) return false;
            $job['status'] = 'running';
            $this->write($directory.'/job.json', $job);
        } finally { $this->unlock($admission); }
        try {
            $payload = $this->read($directory.'/input.json');
            if (!$payload) throw new \RuntimeException('Missing export input');
            $payload['timeout'] = max(1, $job['deadline'] - time());
            $this->render($payload, $directory);
            if (!is_file($directory.'/output.mp4') || filesize($directory.'/output.mp4') === 0) throw new \RuntimeException('Missing export output');
            $job['status'] = 'complete';
            return true;
        } catch (\Throwable $e) {
            $job['status'] = 'failed';
            File::delete($directory.'/output.mp4');
            logger()->warning('Signage export failed', ['job'=>$id, 'exception'=>get_class($e)]);
            return false;
        } finally {
            $this->write($directory.'/job.json', $job);
            File::delete($directory.'/input.json');
            File::deleteDirectory($directory.'/work');
            $this->unlock($lock);
        }
    }

    protected function render(array $payload, string $directory): void
    {
        File::ensureDirectoryExists($directory.'/work', 0700);
        $command = [$payload['node'], base_path('scripts/signage/export/render.cjs'), $directory.'/output.mp4'];
        if (!empty($payload['supervisor'])) {
            // GNU timeout supervises a process group, including FFmpeg children.
            // Give Node time to close Chromium before the group is forcibly killed.
            $command = array_merge([$payload['supervisor'], '--signal=TERM', '--kill-after=15s', $payload['timeout'].'s'], $command);
        }
        $process = new Process($command, $directory.'/work', $payload['env']);
        $payload['progress_file'] = $directory.'/progress.json';
        $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
        $process->setTimeout($payload['timeout'] + 30)->mustRun();
        $probe = new Process([$payload['ffprobe'], '-v', 'error', '-show_entries', 'stream=codec_name,width,height,pix_fmt:format=duration', '-of', 'json', $directory.'/output.mp4']);
        $probe->setTimeout(30)->mustRun();
        $info = json_decode($probe->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $video = $info['streams'][0] ?? [];
        if (($video['codec_name'] ?? '') !== 'h264' || ($video['width'] ?? 0) !== 1920 || ($video['height'] ?? 0) !== 1080
            || ($video['pix_fmt'] ?? '') !== 'yuv420p' || (float) ($info['format']['duration'] ?? 0) < 599.5) throw new \RuntimeException('Invalid export format');
    }

    protected function launch(string $id, string $directory): void
    {
        $php = PHP_BINDIR.(PHP_OS_FAMILY === 'Windows' ? '/php.exe' : '/php');
        $arguments = [$php, base_path('artisan'), 'signage:export-job', $id];
        if (PHP_OS_FAMILY === 'Windows') {
            $quote = fn ($value) => "'".str_replace("'", "''", $value)."'";
            $script = 'Start-Process -WindowStyle Hidden -FilePath '.$quote($php).' -ArgumentList @('.
                implode(',', array_map($quote, ['"'.base_path('artisan').'"', 'signage:export-job', $id])).') -WorkingDirectory '.$quote(base_path()).
                ' -RedirectStandardOutput '.$quote($directory.'/worker.log').' -RedirectStandardError '.$quote($directory.'/worker-error.log');
            $process = new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-EncodedCommand', base64_encode(iconv('UTF-8', 'UTF-16LE', $script))]);
        } else {
            $command = 'nohup setsid '.implode(' ', array_map('escapeshellarg', $arguments)).' >'.escapeshellarg($directory.'/worker.log').' 2>&1 </dev/null &';
            $process = Process::fromShellCommandline($command, base_path());
        }
        $process->setTimeout(10)->mustRun();
    }

    protected function runtime(): array
    {
        $runtime = dirname(base_path()).'/.signage-runtime';
        $finder = new ExecutableFinder();
        $node = $finder->find('node', null, array_merge(glob($runtime.'/node-*/bin') ?: [], [getenv('ProgramFiles').'/nodejs']));
        $paths = array_merge(glob($runtime.'/ffmpeg-*/bin') ?: [], [getenv('LOCALAPPDATA').'/Microsoft/WinGet/Links']);
        $ffmpeg = $finder->find(config('signage.ffmpeg'), null, $paths);
        $ffprobe = $finder->find(config('signage.ffprobe'), null, $paths);
        $supervisor = PHP_OS_FAMILY === 'Windows' ? null : $finder->find('timeout');
        if (!$node || !$ffmpeg || !$ffprobe || (PHP_OS_FAMILY !== 'Windows' && !$supervisor)) throw ValidationException::withMessages(['export'=>'A videókészítő jelenleg nem elérhető. Próbáld újra később.']);
        $env = [];
        if (is_dir($runtime.'/node_modules/playwright-core')) $env = ['NODE_PATH'=>$runtime.'/node_modules', 'PLAYWRIGHT_BROWSERS_PATH'=>$runtime.'/browsers', 'PLAYWRIGHT_HOST_PLATFORM_OVERRIDE'=>'ubuntu22.04-x64'];
        return compact('node', 'ffmpeg', 'ffprobe', 'supervisor', 'env');
    }

    private function payload(array $snapshot, array $runtime): array
    {
        $products = app(SignageCatalog::class)->menuPhotoSnapshot($snapshot['bindings'], new \DateTimeImmutable())['products'];
        $assets = [];
        foreach ($products as &$product) {
            if ($product['image']) {
                $name = basename(parse_url($product['image'], PHP_URL_PATH));
                $product['image'] = '/food/'.rawurlencode($name);
                $assets[$product['image']] = storage_path('app/public/admin-assets/images/item/'.$name);
            }
        }
        unset($product);
        foreach ($snapshot['scenes'] as &$scene) {
            foreach ($scene['reference_assets'] ?? [] as $key => $asset) $scene['reference_assets'][$key]['url'] = '/signage/reference-assets/'.basename($asset['url']);
            foreach (['media', 'background_media', 'logo'] as $key) {
                if (!isset($scene[$key])) continue;
                $media = SignageMedia::findOrFail($scene[$key]['id']);
                $url = '/media/'.$media->checksum.'.'.($media->mime === 'video/mp4' ? 'mp4' : pathinfo($media->path, PATHINFO_EXTENSION));
                $assets[$url] = Storage::disk('local')->path($media->path);
                $scene[$key]['url'] = $url;
            }
        }
        unset($scene);
        return ['scenes'=>$snapshot['scenes'], 'products'=>$products, 'assets'=>$assets] + $runtime;
    }

    private function owned(string $id, int $ownerId): array
    {
        // Refresh and worker admission must share this lock: a status check may
        // briefly inspect render.lock, but must never make the new worker exit.
        $admission = $this->lock('admission', true);
        try {
            $job = $this->read($this->directory($id).'/job.json');
            abort_unless($job && $job['owner_id'] === $ownerId && $job['created_at'] > time() - self::RETENTION, 404);
            return $this->refresh($job);
        } finally { $this->unlock($admission); }
    }

    private function refresh(array $job): array
    {
        if (!in_array($job['status'], ['pending', 'running'], true)) return $job;
        $lock = $this->lock('render', false);
        if ($lock) {
            $job = $this->read($this->directory($job['id']).'/job.json');
            if (($job['status'] === 'pending' && $job['created_at'] < time() - 60) || $job['status'] === 'running') {
                $job['status'] = 'failed';
                $this->write($this->directory($job['id']).'/job.json', $job);
            }
            $this->unlock($lock);
        }
        return $job;
    }

    private function publicState(array $job): array
    {
        $progress = $this->read($this->directory($job['id']).'/progress.json');
        return ['id'=>$job['id'], 'status'=>$job['status'], 'filename'=>$job['filename'], 'total_scenes'=>$job['total_scenes'],
            'scene'=>min($job['total_scenes'], max(0, (int) ($progress['scene'] ?? 0)))];
    }

    private function root(): string
    {
        $path = storage_path('app/signage-exports');
        File::ensureDirectoryExists($path, 0700);
        return $path;
    }

    private function directory(string $id): string
    {
        abort_unless(Str::isUuid($id), 404);
        return $this->root().'/'.$id;
    }

    private function read(string $file): ?array
    {
        return is_file($file) ? json_decode(File::get($file), true) : null;
    }

    private function write(string $file, array $data): void
    {
        File::replace($file, json_encode($data, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE), 0600);
    }

    private function lock(string $name, bool $blocking)
    {
        $handle = fopen($this->root().'/'.$name.'.lock', 'c');
        if (!$handle) throw new \RuntimeException('Cannot open export lock');
        if (!flock($handle, LOCK_EX | ($blocking ? 0 : LOCK_NB))) { fclose($handle); return null; }
        return $handle;
    }

    private function unlock($handle): void
    {
        if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function cleanup(): void
    {
        foreach (glob($this->root().'/*/job.json') ?: [] as $file) {
            $job = $this->read($file);
            if ($job && Str::isUuid(basename(dirname($file))) && $job['created_at'] < time() - self::RETENTION) File::deleteDirectory(dirname($file));
        }
    }
}
