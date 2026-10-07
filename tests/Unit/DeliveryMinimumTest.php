<?php

namespace Tests\Unit;

use App\Services\DeliveryMinimum;
use PHPUnit\Framework\TestCase;

class DeliveryMinimumTest extends TestCase
{
    /** @dataProvider deliveryAmounts */
    public function test_delivery_minimum_excludes_shipping($charge, $total, $blocked): void
    {
        $error = DeliveryMinimum::violation(1, $total, $charge);

        $this->assertSame($blocked, $error !== null);
        if ($blocked) {
            $this->assertSame('min_order_not_met', $error['code']);
        }
    }

    public static function deliveryAmounts(): array
    {
        return [
            'nearby has no minimum' => [560, 660, false],
            'second band starts at 561' => [561, 6460, true],
            'second band below minimum' => [760, 6659, true],
            'second band exact minimum' => [760, 6660, false],
            'second band above minimum' => [760, 6661, false],
            'third band starts at 761' => [761, 8660, true],
            'third band below minimum' => [2200, 10099, true],
            'third band exact minimum' => [2200, 10100, false],
            'third band above minimum' => [2200, 10101, false],
            'fourth band starts at 2201' => [2201, 14100, true],
            'fourth band exact minimum' => [2201, 14101, false],
            'fourth band above minimum' => [2201, 14102, false],
            'formatted HUF below minimum' => ['760.00HUF', '6 659,00 Ft', true],
            'formatted HUF exact minimum' => ['760,00 Ft', '6,660.00HUF', false],
        ];
    }

    public function test_pickup_has_no_delivery_minimum(): void
    {
        $this->assertNull(DeliveryMinimum::violation(2, 500, 2400));
    }

    public function test_error_reports_the_amount_missing_without_shipping(): void
    {
        $error = DeliveryMinimum::violation(1, 6000, 760);

        $this->assertStringContainsString('5900 Ft', $error['message']);
        $this->assertStringContainsString('5240 Ft', $error['message']);
        $this->assertStringContainsString('660 Ft', $error['message']);
    }
}
