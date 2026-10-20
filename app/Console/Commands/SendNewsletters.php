<?php

namespace App\Console\Commands;

use App\Models\Newsletter;
use App\Services\NewsletterSender;
use Illuminate\Console\Command;

class SendNewsletters extends Command
{
    protected $signature = 'newsletter:send {--limit=25 : Maximum recipients per run}';
    protected $description = 'Send pending newsletter deliveries in bounded batches';

    public function handle(NewsletterSender $sender): int
    {
        $remaining = max(1, min(100, (int) $this->option('limit')));
        foreach (Newsletter::where('status', 'sending')->orderBy('id')->cursor() as $newsletter) {
            $before = $newsletter->progress();
            $progress = $sender->process($newsletter, $remaining);
            $remaining -= max(0, $before['pending'] - $progress['pending']);
            if ($remaining <= 0) break;
        }
        return self::SUCCESS;
    }
}
