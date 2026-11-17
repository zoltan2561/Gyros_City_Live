<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UnprocessedOrderAlerts
{
    // Keep the established recipients for pending-order alerts separate from the global admin contact.
    private const RECIPIENTS = [
        'admin@gyroscity.eu',
        'blausilverkft@gmail.com',
        'fuleczkikinga23@gmail.com',
        'markdemetet@gmail.com',
    ];

    public function send(): array
    {
        $count = Order::where('status_type', 1)->where('order_from', '!=', 'pos')
            ->where('created_at', '<=', now()->subMinutes(10))->count();
        if ($count === 0) return ['ok' => true, 'status' => 'idle', 'count' => 0];

        $cacheKey = 'unprocessed_order_alert_lock';
        if (!Cache::add($cacheKey, 1, now()->addMinutes(10))) {
            return ['ok' => true, 'status' => 'throttled', 'count' => $count];
        }
        $adminName = (string) config('mail.admin_name', 'Admin');
        $body = "Legalább 10 perce feldolgozatlan online rendelések száma: " . $count . "\n"
            . "Időpont: " . now()->toDateTimeString() . "\n"
            . "Admin: " . url('/admin');
        try {
            Mail::raw($body, function ($message) use ($adminName) {
                foreach (self::RECIPIENTS as $address) $message->to($address, $adminName);
                $message->subject('Figyelmeztetés: FÜGGŐ online rendelés');
            });
        } catch (\Throwable $exception) {
            Cache::forget($cacheKey);
            Log::warning('Unprocessed order alert failed', ['exception_class' => get_class($exception)]);
            return ['ok' => false, 'status' => 'failed', 'count' => $count];
        }
        return ['ok' => true, 'status' => 'sent', 'count' => $count];
    }
}
