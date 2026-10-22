<?php

namespace Tests\Feature;

use App\Helpers\helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CurrencyFormattingPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'currency_test', 'database.connections.currency_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('currency')->default('HUF');
            $t->integer('currency_position')->default(2); $t->integer('decimal_separator')->default(1);
            $t->integer('currency_space')->default(1); $t->integer('currency_formate')->default(0);
            foreach (['app_bottom_image', 'booknow_bg_image', 'why_choose_image'] as $column) $t->string($column)->nullable();
        });
        DB::table('settings')->insert(['id' => 1]);
    }

    public function test_price_formatting_reads_settings_once_and_keeps_current_formatting(): void
    {
        DB::enableQueryLog();
        $this->assertSame('2,340 HUF', helper::currency_format(2340));
        $this->assertCount(1, DB::getQueryLog());
        DB::table('settings')->update(['currency' => 'EUR', 'currency_position' => 1,
            'currency_space' => 0, 'currency_formate' => 2, 'decimal_separator' => 2]);
        $this->assertSame('EUR2.340,50', helper::currency_format(2340.5));
    }
}
