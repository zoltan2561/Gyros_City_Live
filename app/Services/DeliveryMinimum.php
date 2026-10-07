<?php

namespace App\Services;

class DeliveryMinimum
{
    // Existing delivery-charge bands, shared by checkout and online payment.
    public const BANDS = [
        ['max_charge' => 560, 'minimum' => 0],
        ['max_charge' => 760, 'minimum' => 5900],
        ['max_charge' => 2200, 'minimum' => 7900],
        ['max_charge' => null, 'minimum' => 11900],
    ];

    public static function violation($orderType, $grandTotal, $deliveryCharge): ?array
    {
        if ((int) $orderType !== 1) {
            return null;
        }

        $charge = (int) round(self::amount($deliveryCharge));
        $total = max(0, (int) round(self::amount($grandTotal)) - $charge);
        $minimum = 0;

        foreach (self::BANDS as $band) {
            if ($band['max_charge'] === null || $charge <= $band['max_charge']) {
                $minimum = $band['minimum'];
                break;
            }
        }

        if ($total >= $minimum) {
            return null;
        }

        return [
            'code' => 'min_order_not_met',
            'message' => 'Nincs meg a minimum rendelési összeg: '.$minimum.' Ft. '
                .'Jelenlegi (szállítás nélkül): '.$total.' Ft. Hiányzik: '.($minimum - $total).' Ft.',
        ];
    }

    private static function amount($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = preg_replace('/[^\d.,]/u', '', (string) $value);
        if (strpos($value, ',') !== false && strpos($value, '.') === false) {
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
