<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Models\NewsletterDelivery;
use App\Models\Subscribe;
use Illuminate\Support\Facades\DB;

class NewsletterUnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $this->delivery($token);
        return view('web.newsletter-unsubscribe', ['token' => $token, 'done' => false]);
    }

    public function unsubscribe(string $token)
    {
        $delivery = $this->delivery($token);
        DB::transaction(function () use ($delivery) {
            Subscribe::whereRaw('LOWER(TRIM(email)) = ?', [$delivery->email])->delete();
            NewsletterDelivery::where('email', $delivery->email)->where('status', 'pending')->update(['status' => 'skipped']);
        });
        return view('web.newsletter-unsubscribe', ['token' => $token, 'done' => true]);
    }

    private function delivery(string $token): NewsletterDelivery
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D', $token), 404);
        return NewsletterDelivery::where('token', $token)->firstOrFail();
    }
}
