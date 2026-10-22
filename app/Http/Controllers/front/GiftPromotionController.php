<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Services\GiftPromotions;
use Illuminate\Http\Request;

class GiftPromotionController extends Controller
{
    public function index(Request $request, GiftPromotions $service)
    {
        $buynow = (int) $request->input('buynow', 0) === 1 ? 1 : 0;
        $offers = $service->offers($service->cart($buynow), $buynow);
        return response()->json([
            'html' => view('web.cart.gifts', compact('offers', 'buynow'))->render(),
            'pending' => $offers->contains(fn ($o) => $o['gifts']->isNotEmpty() && $o['pending']),
        ]);
    }

    public function choose(Request $request, GiftPromotions $service)
    {
        $data = $request->validate([
            'promotion_id' => 'required|integer', 'choices' => 'required|array|max:1000',
            'choices.*' => 'required|integer', 'extra_choices' => 'nullable|array|max:1000',
            'extra_choices.*' => 'nullable|integer|min:0', 'buynow' => 'required|integer|in:0,1',
        ]);
        if (!$service->choose((int) $data['promotion_id'], $data['choices'], (int) $data['buynow'], $data['extra_choices'] ?? [])) {
            return response()->json(['message' => 'A kosár vagy az ajándék elérhetősége megváltozott. Válassz újra.'], 422);
        }
        return $this->index($request, $service);
    }
}
