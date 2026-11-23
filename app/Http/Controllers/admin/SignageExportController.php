<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SignageMedia;
use App\Services\Signage\SignageCatalog;
use App\Services\Signage\SignagePublisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class SignageExportController extends Controller
{
    public function __invoke(int $id, Request $request, SignagePublisher $publisher, SignageCatalog $catalog)
    {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        $snapshot = $publisher->build($id);
        $products = $catalog->menuPhotoSnapshot($snapshot['bindings'], new \DateTimeImmutable())['products'];
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
            foreach ($scene['reference_assets'] ?? [] as $key => $asset) {
                $scene['reference_assets'][$key]['url'] = '/signage/reference-assets/'.basename($asset['url']);
            }
            foreach (['media','background_media','logo'] as $key) {
                if (isset($scene[$key])) {
                    $media = SignageMedia::findOrFail($scene[$key]['id']);
                    $url = '/media/'.$media->checksum.'.'.($media->mime === 'video/mp4' ? 'mp4' : pathinfo($media->path, PATHINFO_EXTENSION));
                    $assets[$url] = Storage::disk('local')->path($media->path);
                    $scene[$key]['url'] = $url;
                }
            }
        }
        unset($scene);
        $job = (string) Str::uuid();
        $directory = storage_path('app/signage-exports/'.$job);
        File::ensureDirectoryExists($directory);
        $output = dirname($directory).'/'.$job.'.mp4';
        try {
            // Apache on Windows can retain an older PATH than the interactive shell.
            $finder = new ExecutableFinder();
            $node = $finder->find('node', 'node', [getenv('ProgramFiles').'/nodejs']);
            $encoder = config('signage.ffmpeg');
            $payload = ['scenes' => $snapshot['scenes'], 'products' => $products, 'assets' => $assets,
                'ffmpeg' => $finder->find($encoder, $encoder, [getenv('LOCALAPPDATA').'/Microsoft/WinGet/Links'])];
            $process = new Process([$node, base_path('scripts/signage/export/render.cjs'), $output], $directory);
            $process->setInput(json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
            $seconds = array_sum(array_map(fn ($s) => max($s['duration'], $s['media']['duration'] ?? 0), $snapshot['scenes']));
            $timeout = 180 + 3 * $seconds + 30 * count($snapshot['scenes']);
            set_time_limit((int) $timeout + 60);
            $process->setTimeout($timeout)->mustRun();
            $name = (Str::slug($snapshot['name']) ?: 'gyros-city').'-'.now()->format('Ymd-His').'.mp4';
            return response()->download($output, $name, ['Content-Type'=>'video/mp4','Cache-Control'=>'private, no-store'])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            File::delete($output);
            // Process exceptions can include input. Keep the catalog out of logs.
            logger()->warning('Signage MP4 export failed', ['playlist'=>$id,'error'=>isset($process) ? substr($process->getErrorOutput(), -1600) : get_class($e)]);
            throw ValidationException::withMessages(['export'=>'Nem sikerült elkészíteni az MP4-et. A helyi Laravel-naplóban megtalálod a renderelési hibát.']);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
