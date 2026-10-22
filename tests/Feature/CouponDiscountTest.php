<?php

namespace Tests\Feature;

use App\Services\CouponDiscount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CouponDiscountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'coupon_test', 'database.connections.coupon_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'session.driver' => 'array']);
        Schema::create('promocode', function (Blueprint $t) {
            $t->id(); $t->string('offer_code'); $t->integer('is_available')->default(1);
            $t->string('start_date'); $t->string('expire_date'); $t->decimal('min_amount')->default(0);
            $t->integer('usage_type')->default(2); $t->integer('usage_limit')->default(10);
            $t->integer('offer_type')->default(2); $t->decimal('offer_amount')->default(10);
        });
        DB::table('promocode')->insert(['offer_code' => 'TEST', 'start_date' => now()->subDay()->toDateString(), 'expire_date' => now()->addDay()->toDateString()]);
        session()->put('discount_data', ['offer_code' => 'TEST', 'offer_amount' => 999999]);
    }

    public function test_discount_is_recomputed_from_the_server_subtotal(): void
    {
        $this->assertSame(100.0, app(CouponDiscount::class)->amount(1000));
        $this->assertSame(100.0, session('discount_data')['offer_amount']);
    }

    public function test_a_cart_below_the_coupon_minimum_cannot_keep_a_previous_discount(): void
    {
        DB::table('promocode')->update(['min_amount' => 5000]);
        try { app(CouponDiscount::class)->amount(1000); $this->fail('Coupon should be rejected'); }
        catch (ValidationException $exception) { $this->assertFalse(session()->has('discount_data')); }
    }

    public function test_disabled_coupons_are_rechecked_at_checkout(): void
    {
        DB::table('promocode')->update(['is_available' => 0]);
        $this->expectException(ValidationException::class);
        app(CouponDiscount::class)->amount(1000);
    }
}
