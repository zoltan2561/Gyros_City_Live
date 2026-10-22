<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\helper;
use App\Models\Promocode;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class PromocodeController extends Controller
{
    public function checkpromocode(Request $request)
    {
        $request->validate(['offer_code' => 'required|string|max:100']);
        $cart = \App\Models\Cart::where('buynow', (int) $request->input('buynow', 0));
        if (auth()->check() && (int) auth()->user()->type === 2) $cart->where('user_id', auth()->id());
        else $cart->where('session_id', session()->getId());
        $rows = $cart->get();
        $subtotal = $rows->sum(fn ($line) => ((float) $line->item_price + (float) $line->addons_total_price + (float) $line->extras_total_price) * (int) $line->qty);
        if ($rows->isEmpty()) return back()->with('error', trans('messages.cart_is_empty1'));
        session()->put('discount_data', ['offer_code' => $request->offer_code]);
        try {
            app(\App\Services\CouponDiscount::class)->amount((float) $subtotal);
            return back()->with('success', trans('messages.success'));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first());
        }
    }

    public function removepromocode()
    {
        session()->forget('discount_data');
        return redirect()->back()->with('success', trans('messages.success'));
    }
}
