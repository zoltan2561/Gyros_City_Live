<?php
namespace App\Jobs;
use App\Services\Signage\SignageMediaProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ProcessSignageMedia implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout=1900;
    public $tries=1;
    public function __construct(public int $mediaId) {
        $this->onConnection(config('signage.queue_connection'))->onQueue(config('signage.queue'));
    }
    public function handle(SignageMediaProcessor $processor): void { $processor->process($this->mediaId); }
}
