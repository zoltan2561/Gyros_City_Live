<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontIndexesTest extends TestCase
{
    public function test_lookup_indexes_preserve_rows_and_can_be_rerun_and_rolled_back(): void
    {
        config(['database.default' => 'indexes_test', 'database.connections.indexes_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('cart', function (Blueprint $t) {
            $t->id(); $t->text('session_id'); $t->integer('buynow'); $t->integer('user_id'); $t->string('item_id');
        });
        Schema::create('order_details', function (Blueprint $t) { $t->id(); $t->integer('order_id'); });
        DB::table('cart')->insert(['session_id' => 'visitor', 'buynow' => 0, 'user_id' => 5, 'item_id' => '2']);
        DB::table('order_details')->insert(['order_id' => 42]);
        $migration = require base_path('database/migrations/2026_10_22_000003_add_storefront_lookup_indexes.php');
        $migration->up(); $migration->up();
        foreach ([['SELECT * FROM cart WHERE session_id = ? AND buynow = 0', ['visitor'], 'gc_cart_session_open_item'],
            ['SELECT * FROM cart WHERE user_id = ? AND buynow = 0', [5], 'gc_cart_user_open_item'],
            ['SELECT * FROM order_details WHERE order_id = ?', [42], 'gc_order_details_order']] as [$sql, $bindings, $index]) {
            $this->assertStringContainsString($index, DB::select('EXPLAIN QUERY PLAN '.$sql, $bindings)[0]->detail);
        }
        $migration->down();
        $this->assertCount(0, DB::select('PRAGMA index_list(cart)'));
        $this->assertSame('visitor', DB::table('cart')->value('session_id'));
        $this->assertSame(42, DB::table('order_details')->value('order_id'));
    }
}
