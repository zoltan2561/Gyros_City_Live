<?php

namespace App\Services;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscribe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class NewsletterSender
{
    public function recipientCount(): int
    {
        $emails = [];
        Subscribe::orderBy('id')->chunkById(500, function ($subscribers) use (&$emails) {
            foreach ($subscribers as $subscriber) {
                $email = $this->normalize($subscriber->email);
                if ($email !== null) $emails[$email] = true;
            }
        });
        return count($emails);
    }

    public function start(Newsletter $newsletter): void
    {
        DB::transaction(function () use ($newsletter) {
            $locked = Newsletter::whereKey($newsletter->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'draft', 409);
            Subscribe::orderBy('id')->chunkById(100, function ($subscribers) use ($locked) {
                $rows = [];
                foreach ($subscribers as $subscriber) {
                    $email = $this->normalize($subscriber->email);
                    if ($email === null) continue;
                    $rows[] = ['newsletter_id' => $locked->id, 'email' => $email,
                        'token' => bin2hex(random_bytes(32)), 'status' => 'pending',
                        'created_at' => now(), 'updated_at' => now()];
                }
                if ($rows) NewsletterDelivery::insertOrIgnore($rows);
            });
            if (!$locked->deliveries()->exists()) {
                throw ValidationException::withMessages(['recipients' => trans('newsletter.no_subscribers')]);
            }
            $locked->status = 'sending';
            $locked->started_at = now();
            $locked->save();
        });
    }

    public function process(Newsletter $newsletter, int $limit = 5): array
    {
        if ($newsletter->fresh()->status !== 'sending') return $newsletter->progress();
        $deliveries = $newsletter->deliveries()->where('status', 'pending')->orderBy('id')->limit($limit)->get();
        foreach ($deliveries as $delivery) {
            // A conditional claim also protects against concurrent browser / cron batches.
            $claimed = NewsletterDelivery::whereKey($delivery->id)->where('status', 'pending')
                ->update(['status' => 'sending', 'updated_at' => now()]);
            if (!$claimed) continue;
            if (!Subscribe::whereRaw('LOWER(TRIM(email)) = ?', [$delivery->email])->exists()) {
                $delivery->update(['status' => 'skipped']);
                continue;
            }
            try {
                // Every recipient receives a separate message; no exposed recipient list.
                Mail::to($delivery->email)->send(new NewsletterMail($newsletter->subject, $newsletter->body,
                    route('newsletter.unsubscribe', ['token' => $delivery->token])));
                $delivery->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (\Throwable $exception) {
                $delivery->update(['status' => 'failed']);
                Log::warning('Newsletter delivery failed', ['newsletter_id' => $newsletter->id,
                    'delivery_id' => $delivery->id, 'exception_class' => get_class($exception)]);
            }
        }
        $progress = $newsletter->progress();
        if ($progress['finished']) Newsletter::whereKey($newsletter->id)->where('status', 'sending')->update(['status' => 'completed']);
        return $progress;
    }

    private function normalize(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
