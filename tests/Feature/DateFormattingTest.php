<?php

namespace Tests\Feature;

use App\Helpers\helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DateFormattingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'date_test', 'database.connections.date_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('date_format');
            foreach (['app_bottom_image', 'booknow_bg_image', 'why_choose_image'] as $name) $t->string($name)->nullable();
        });
        DB::table('settings')->insert(['date_format' => 'Y-m-d']);
    }

    /** @dataProvider missingDates */
    public function test_missing_and_invalid_dates_never_turn_into_epoch_dates($date): void
    {
        $this->assertSame('—', helper::date_format($date));
    }

    public static function missingDates(): array
    {
        return [[null], [''], ['  '], ['not-a-date'], ['0000-00-00'], ['0000-00-00 00:00:00'], ['2026-02-30']];
    }

    public function test_a_real_date_keeps_the_configured_format(): void
    {
        $this->assertSame('2026-10-20', helper::date_format('2026-10-20 14:30:00'));
        DB::table('settings')->update(['date_format' => 'd.m.Y']);
        $this->assertSame('20.10.2026', helper::date_format('2026-10-20'));
    }

    public function test_unscheduled_pickup_has_a_human_label_without_a_fake_time(): void
    {
        $html = view('web.orders.delivery-schedule', ['orderdata' => (object) [
            'order_type' => 2, 'delivery_date' => null, 'delivery_time' => null,
        ]])->render();
        $this->assertStringContainsString('Elkészülés után', $html);
        $this->assertStringNotContainsString('1970', $html);
        $this->assertStringNotContainsString('00:00', $html);
    }

    public function test_scheduled_pickup_keeps_its_date_and_time(): void
    {
        $html = view('web.orders.delivery-schedule', ['orderdata' => (object) [
            'order_type' => 2, 'delivery_date' => '2026-10-21', 'delivery_time' => '12:00–12:30',
        ]])->render();
        $this->assertStringContainsString('2026-10-21', $html);
        $this->assertStringContainsString('12:00–12:30', $html);
    }
}
