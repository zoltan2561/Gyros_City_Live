<?php

namespace Tests\Feature;

use App\Models\SignagePlaylist;
use App\Models\SignageScene;
use App\Services\Signage\SignageExport;
use Illuminate\Support\Facades\File;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignageExportTest extends SignageCatalogTest
{
    private string $originalStorage;
    private string $temporaryStorage;
    private SignagePlaylist $playlist;

    protected function setUp(): void
    {
        parent::setUp();
        (require base_path('database/migrations/2026_11_09_000001_create_signage_tables.php'))->up();
        $scene = SignageScene::create(['name'=>'Export test', 'content'=>[
            'type'=>'grid', 'theme'=>'dark', 'title'=>'Menu', 'duration'=>5,
            'bindings'=>[['id'=>'burger', 'item_id'=>1]],
        ]]);
        $this->playlist = SignagePlaylist::create(['name'=>'City menu', 'scene_ids'=>[$scene->id]]);
        $this->originalStorage = storage_path();
        $this->temporaryStorage = sys_get_temp_dir().'/gc-export-test-'.bin2hex(random_bytes(8));
        $this->app->useStoragePath($this->temporaryStorage);
    }

    protected function tearDown(): void
    {
        $this->app->useStoragePath($this->originalStorage);
        File::deleteDirectory($this->temporaryStorage);
        parent::tearDown();
    }

    private function exporter(): SignageExport
    {
        $this->assertTrue(class_exists(SignageExport::class), 'The background export service must exist.');
        $service = Mockery::mock(SignageExport::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('runtime')->andReturn(['node'=>'node', 'ffmpeg'=>'ffmpeg', 'ffprobe'=>'ffprobe', 'env'=>[]]);
        $service->shouldReceive('launch')->andReturnNull()->byDefault();
        return $service;
    }

    public function test_start_returns_private_pending_job_and_a_reload_finds_it(): void
    {
        $service = $this->exporter();
        $job = $service->start($this->playlist->id, 12);
        $this->assertSame('pending', $job['status']);
        $this->assertSame($job['id'], $service->latest($this->playlist->id, 12)['id']);
        $this->assertNull($service->latest($this->playlist->id, 99));
        $this->assertArrayNotHasKey('assets', $job);
        $this->assertArrayNotHasKey('owner_id', $job);
        $this->assertArrayNotHasKey('directory', $job);
        $this->assertSame(1, $job['total_scenes']);
    }

    public function test_second_admin_cannot_start_a_parallel_job(): void
    {
        $service = $this->exporter();
        $service->start($this->playlist->id, 12);
        try { $service->start($this->playlist->id, 99); $this->fail('Parallel export was accepted.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_another_user_and_invalid_identifiers_cannot_read_a_job(): void
    {
        $service = $this->exporter();
        $job = $service->start($this->playlist->id, 12);
        foreach ([[$job['id'], 99], ['../../.env', 12]] as [$id, $owner]) {
            try { $service->status($id, $owner); $this->fail('Private job was exposed.'); }
            catch (HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
        }
    }

    public function test_pending_and_partial_output_cannot_be_downloaded(): void
    {
        $service = $this->exporter();
        $job = $service->start($this->playlist->id, 12);
        File::put(storage_path('app/signage-exports/'.$job['id'].'/output.mp4'), 'partial');
        try { $service->downloadPath($job['id'], 12); $this->fail('Partial video was exposed.'); }
        catch (HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_worker_completes_job_and_allows_a_protected_download(): void
    {
        $service = $this->exporter();
        $service->shouldReceive('render')->once()->andReturnUsing(function ($payload, $directory) {
            File::put($directory.'/output.mp4', 'verified-video');
        });
        $job = $service->start($this->playlist->id, 12);
        $this->assertTrue($service->run($job['id']));
        $this->assertSame('complete', $service->status($job['id'], 12)['status']);
        $this->assertSame('verified-video', File::get($service->downloadPath($job['id'], 12)));
        try { $service->downloadPath($job['id'], 99); $this->fail('Another user downloaded the video.'); }
        catch (HttpException $e) { $this->assertSame(404, $e->getStatusCode()); }
        $this->assertSame('pending', $service->start($this->playlist->id, 99)['status']);
    }

    public function test_render_failure_releases_capacity_and_hides_internal_errors(): void
    {
        $service = $this->exporter();
        $service->shouldReceive('render')->once()->andThrow(new \RuntimeException('private filesystem details'));
        $job = $service->start($this->playlist->id, 12);
        $this->assertFalse($service->run($job['id']));
        $status = $service->status($job['id'], 12);
        $this->assertSame('failed', $status['status']);
        $this->assertStringNotContainsString('private filesystem details', json_encode($status));
        $this->assertSame('pending', $service->start($this->playlist->id, 99)['status']);
    }

    public function test_launch_failure_does_not_leave_a_pending_reservation(): void
    {
        $service = $this->exporter();
        $service->shouldReceive('launch')->once()->andThrow(new \RuntimeException('launch failed'))->ordered();
        try { $service->start($this->playlist->id, 12); $this->fail('Launch failure was ignored.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('export', $e->errors()); }
        $this->assertSame('failed', $service->latest($this->playlist->id, 12)['status']);
    }

    public function test_abandoned_pending_job_is_failed_and_a_late_worker_cannot_replace_the_new_job(): void
    {
        $service = $this->exporter();
        $old = $service->start($this->playlist->id, 12);
        $path = storage_path('app/signage-exports/'.$old['id'].'/job.json');
        $record = json_decode(File::get($path), true);
        $record['created_at'] = time() - 300;
        File::put($path, json_encode($record));
        $this->assertSame('failed', $service->status($old['id'], 12)['status']);
        $new = $service->start($this->playlist->id, 99);
        $this->assertFalse($service->run($old['id']));
        $this->assertSame('pending', $service->status($new['id'], 99)['status']);
    }

    public function test_status_waits_for_worker_admission_instead_of_stealing_its_render_lock(): void
    {
        $service = $this->exporter();
        $job = $service->start($this->playlist->id, 12);
        $lock = fopen(storage_path('app/signage-exports/admission.lock'), 'c');
        flock($lock, LOCK_EX);
        $root = var_export(base_path(), true);
        $storage = var_export($this->temporaryStorage, true);
        $script = '<?php require '.$root.'."/tests/offline-prepend.php"; require '.$root.'."/vendor/autoload.php";'.
            '$app=require '.$root.'."/bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();'.
            '$app->useStoragePath('.$storage.'); echo "ready\\n"; flush();'.
            'echo json_encode($app->make(App\\Services\\Signage\\SignageExport::class)->status("'.$job['id'].'",12));';
        $process = new \Symfony\Component\Process\Process([PHP_BINARY]);
        $process->setInput($script)->setTimeout(10)->start();
        try {
            $this->assertTrue($process->waitUntil(fn ($type, $output) => str_contains($output, 'ready')));
            usleep(150000);
            $this->assertTrue($process->isRunning(), 'Status must wait until worker admission is released.');
        } finally { flock($lock, LOCK_UN); fclose($lock); }
        $this->assertSame(0, $process->wait(), $process->getErrorOutput());
        $this->assertStringContainsString('"status":"pending"', $process->getOutput());
    }

    public function test_renderer_start_failure_keeps_only_private_stderr_for_diagnosis(): void
    {
        $directory = $this->temporaryStorage.'/renderer-failure';
        File::ensureDirectoryExists($directory, 0700);
        $method = new \ReflectionMethod(SignageExport::class, 'render');
        try {
            $method->invoke(new SignageExport(), ['node'=>$directory.'/missing-node', 'env'=>[], 'timeout'=>2], $directory);
            $this->fail('A missing renderer executable succeeded.');
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            $this->assertFileExists($directory.'/renderer-error.log');
            $this->assertNotSame('', File::get($directory.'/renderer-error.log'));
        }
    }

    public function test_scheduled_dispatch_runs_one_waiting_job_and_is_idle_after_completion(): void
    {
        $service = $this->exporter();
        $service->shouldReceive('render')->once()->andReturnUsing(fn ($payload, $directory) => File::put($directory.'/output.mp4', 'scheduled-video'));
        $this->assertTrue(method_exists($service, 'dispatchPending'), 'A scheduled dispatcher must be available.');
        $this->assertFalse($service->dispatchPending());
        $job = $service->start($this->playlist->id, 12);
        $this->assertTrue($service->dispatchPending());
        $this->assertSame('complete', $service->status($job['id'], 12)['status']);
        $this->assertFalse($service->dispatchPending());
    }
}
