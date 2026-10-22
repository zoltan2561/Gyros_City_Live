<?php

namespace App\Services;

use App\Helpers\helper;
use App\Models\Payment;
use App\Models\Shippingarea;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CheckoutPricing
{
    public function quote(Collection $cart, int $orderType, $areaId): array
    {
        $subtotal = app(CatalogPricing::class)->refresh($cart);
        $settings = helper::appdata();
        if (($settings->max_order_qty > 0 && $cart->sum('qty') > $settings->max_order_qty)
            || $subtotal < (float) $settings->min_order_amount
            || ($settings->max_order_amount > 0 && $subtotal > (float) $settings->max_order_amount)) {
            throw ValidationException::withMessages(['cart' => 'A rendelés mennyisége vagy összege a megengedett határon kívül van.']);
        }
        $charge = 0;
        if ($orderType === 1) {
            $area = ctype_digit((string) $areaId) ? Shippingarea::find($areaId) : null;
            if (!$area || $subtotal < (float) $area->min_order) {
                throw ValidationException::withMessages(['delivery_area' => 'Ellenőrizd a kiszállítási területet és a minimum rendelést.']);
            }
            $charge = max(0, (float) $area->delivery_charge);
        }
        $taxes = [];
        foreach ($cart as $line) {
            if (trim((string) $line->tax) === '') continue;
            foreach (helper::gettax($line->tax) as $tax) {
                if (!$tax) continue;
                $amount = (int) $tax->type === 1 ? (float) $tax->tax * (int) $line->qty
                    : (float) $tax->tax / 100 * ((float) $line->item_price + (float) $line->addons_total_price) * (int) $line->qty;
                $taxes[$tax->name] = ($taxes[$tax->name] ?? 0) + $amount;
            }
        }
        $discount = app(CouponDiscount::class)->amount($subtotal);
        return ['subtotal' => $subtotal, 'delivery_charge' => $charge,
            'tax' => implode('|', array_values($taxes)), 'tax_name' => implode('|', array_keys($taxes)),
            'grand_total' => round(max(0, $subtotal + array_sum($taxes) + $charge - $discount), 2)];
    }

    public function requirePayment(int $type): void
    {
        if (!Payment::where('payment_type', $type)->where('is_activate', 1)->where('is_available', 1)->exists()) {
            throw ValidationException::withMessages(['transaction_type' => 'A választott fizetési mód nem elérhető.']);
        }
    }
}
