<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\OrderController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOrderListTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'order_list_test', 'database.connections.order_list_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('order', function (Blueprint $t) {
            $t->id(); $t->string('order_number'); $t->string('name'); $t->string('mobile');
            $t->string('order_from'); $t->integer('status_type');
        });
        Schema::create('custom_status', function (Blueprint $t) {
            $t->id(); $t->integer('type'); $t->integer('order_type'); $t->string('name');
        });
        Schema::create('payment', function (Blueprint $t) {
            $t->id(); $t->integer('payment_type'); $t->string('payment_name');
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('type'); $t->boolean('is_available');
        });
        DB::table('users')->insert(['name' => 'QA Driver', 'type' => 3, 'is_available' => 1]);
        for ($id = 1; $id <= 23; $id++) {
            DB::table('order')->insert(['id' => $id, 'order_number' => 'QA-'.$id,
                'name' => $id === 1 ? 'Old Customer' : 'Customer '.$id, 'mobile' => '0620'.str_pad($id, 7, '0', STR_PAD_LEFT),
                'order_from' => 'web', 'status_type' => $id <= 3 ? 1 : ($id <= 5 ? 4 : 3)]);
        }
        DB::table('order')->insert(['id' => 24, 'order_number' => 'POS-24', 'name' => 'Old Customer',
            'mobile' => '000', 'order_from' => 'pos', 'status_type' => 1]);
    }

    private function listing(string $query = ''): array
    {
        $request = Request::create('http://localhost/admin/orders'.$query);
        $this->app->instance('request', $request);
        return app(OrderController::class)->index($request)->getData();
    }

    public function test_initial_page_loads_only_the_latest_ten_without_pos_orders(): void
    {
        DB::enableQueryLog();
        $data = $this->listing();
        $this->assertCount(10, $data['getorders']);
        $this->assertSame(range(23, 14), $data['getorders']->pluck('id')->all());
        $this->assertSame(23, $data['getorders']->total());
        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()));
        $this->assertSame('QA Driver', $data['getdriver']->first()->name);
        $this->assertSame(23, $data['totalorders']);
        $this->assertSame(3, $data['totalprocessing']);
        $this->assertSame(18, $data['totalcompleted']);
        $this->assertSame(2, $data['totalcancelled']);
    }

    public function test_older_orders_remain_available_on_following_pages(): void
    {
        $data = $this->listing('?page=3');
        $this->assertSame([3, 2, 1], $data['getorders']->pluck('id')->all());
        $this->assertSame(21, $data['getorders']->firstItem());
    }

    public function test_search_finds_an_old_order_outside_the_first_page(): void
    {
        $data = $this->listing('?q=Old%20Customer');
        $this->assertSame([1], $data['getorders']->pluck('id')->all());
        $this->assertSame(23, $data['totalorders']);
    }

    public function test_search_and_status_filter_apply_together_and_survive_pagination(): void
    {
        $data = $this->listing('?status=completed&q=Customer');
        $this->assertSame(18, $data['getorders']->total());
        $this->assertStringContainsString('status=completed', $data['getorders']->nextPageUrl());
        $this->assertStringContainsString('q=Customer', $data['getorders']->nextPageUrl());
        $this->assertSame(0, $this->listing('?status=cancelled&q=Old%20Customer')['getorders']->total());
    }

    public function test_search_accepts_order_numbers_and_phone_numbers(): void
    {
        $this->assertSame([1], $this->listing('?q=QA-1&status=processing')['getorders']->pluck('id')->all());
        $this->assertSame([2], $this->listing('?q=06200000002')['getorders']->pluck('id')->all());
    }
}
