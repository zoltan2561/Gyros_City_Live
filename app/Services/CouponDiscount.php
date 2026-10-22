<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Promocode;
use Illuminate\Validation\ValidationException;

class CouponDiscount
{
    public function amount(float $subtotal): float
    {
        $code = (string) (session('discount_data')['offer_code'] ?? '');
        if ($code === '') return 0;
        $coupon = Promocode::where('offer_code', $code)->where('is_available', 1)->first();
        $today = now()->toDateString();
        if (!$coupon || $today < $coupon->start_date || $today > $coupon->expire_date
            || $subtotal < (float) $coupon->min_amount
            || ((int) $coupon->usage_type === 1 && Order::where('offer_code', $code)->count() >= (int) $coupon->usage_limit)) {
            session()->forget('discount_data');
            throw ValidationException::withMessages(['offer_code' => 'A kupon már nem használható ehhez a kosárhoz.']);
        }
        $amount = (int) $coupon->offer_type === 1 ? (float) $coupon->offer_amount : $subtotal * (float) $coupon->offer_amount / 100;
        $amount = round(min($subtotal, max(0, $amount)), 2);
        session()->put('discount_data', ['offer_code' => $code, 'offer_amount' => $amount]);
        return $amount;
    }
}
