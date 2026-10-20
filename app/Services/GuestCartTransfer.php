<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GuestCartTransfer
{
    public function adopt(User $customer, string $guestSessionId): void
    {
        if ($guestSessionId === '') return;

        [$buckets, $previousQuickMoved] = DB::transaction(function () use ($customer, $guestSessionId) {
            // Serialize transfers into this account, including its separate quick-order basket.
            User::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $guestRows = $this->guestRows($guestSessionId)->lockForUpdate()->get(['id', 'buynow']);
            if ($guestRows->isEmpty()) return [[], false];

            $buckets = $guestRows->pluck('buynow')->map(fn ($value) => (int) $value)->unique()->all();
            $previousQuickMoved = false;
            if (in_array(1, $buckets, true)) {
                // Keep an older quick order as regular cart items; only the current quick order goes to its checkout.
                $previousQuickMoved = Cart::where('user_id', $customer->id)->where('buynow', 1)->update(['buynow' => 0]) > 0;
            }
            $this->guestRows($guestSessionId)->whereIn('id', $guestRows->pluck('id'))->update([
                'user_id' => $customer->id,
                'session_id' => '',
            ]);
            return [$buckets, $previousQuickMoved];
        });

        if ($buckets !== []) {
            app(GiftPromotions::class)->transferGuestChoices((int) $customer->id, $buckets, $previousQuickMoved);
            session()->forget('discount_data');
        }
    }

    public function keepGuestSession(string $previousId, string $currentId): void
    {
        if ($previousId !== '' && $previousId !== $currentId) {
            $this->guestRows($previousId)->update(['session_id' => $currentId]);
        }
    }

    private function guestRows(string $sessionId): Builder
    {
        return Cart::where('session_id', $sessionId)->where(function ($query) {
            // Old guest rows may use an empty string or zero instead of NULL.
            $query->whereNull('user_id')->orWhereIn('user_id', ['', '0']);
        });
    }
}
