<?php

namespace Tests\Unit;

use App\Services\DeliveryAreaNames;
use PHPUnit\Framework\TestCase;

class DeliveryAreaNamesTest extends TestCase
{
    /** @dataProvider labels */
    public function test_settlement_names_are_extracted_without_fees_or_damaged_hyphens($label, $expected): void
    {
        $this->assertSame($expected, DeliveryAreaNames::fromLabel($label));
    }

    public static function labels(): array
    {
        return [
            ['Vásárosnamény - 340 Ft ', ['Vásárosnamény']],
            ['Perényi-tanya - 1100 Ft ', ['Perényi-tanya']],
            ['Tiszaszalka - 1900 Ft (min. rend.: 4900 Ft)', ['Tiszaszalka']],
            ['Tarpa,gulács felé - 2800 Ft (min. rend.: 5900 Ft)', ['Tarpa', 'gulács']],
            ['Kis falu — 1 200 HUF', ['Kis falu']],
            ['', []],
        ];
    }
}
