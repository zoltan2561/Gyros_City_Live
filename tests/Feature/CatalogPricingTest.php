<?php

namespace Tests\Feature;

use App\Http\Controllers\front\CheckoutController;
use App\Models\Cart;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\MaintenanceMiddleware;
use Illuminate\Support\Facades\Session;
use App\Models\Item;
use App\Services\CatalogPricing;
use App\Services\CheckoutPricing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CatalogPricingTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'gift_test', 'database.connections.gift_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array']);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        $this->withoutMiddleware([MaintenanceMiddleware::class, AdminAuth::class]);
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('category_name'); $t->string('slug'); $t->string('image')->default('');
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->integer('cat_id'); $t->string('item_name'); $t->string('slug');
            $t->integer('item_status')->default(1); $t->boolean('today_unavailable')->default(false);
            $t->integer('item_type')->default(2); $t->integer('price')->default(1000); $t->timestamps();
        });
        Schema::table('item', function (Blueprint $t) { $t->string('addons_id')->nullable(); });
        Schema::create('addons_group', function (Blueprint $t) { $t->id(); $t->string('name')->default('Test'); $t->integer('selection_type')->default(2); $t->integer('selection_count')->default(2); $t->integer('min_count')->default(0); $t->integer('max_count')->default(5); $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2); });
        Schema::create('addons', function (Blueprint $t) { $t->id(); $t->integer('addongroup_id'); $t->string('name'); $t->decimal('price'); $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2); });
        Schema::create('extras', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('name'); $t->decimal('price'); });
        Schema::create('item_images', function (Blueprint $t) {
            $t->id(); $t->integer('item_id'); $t->string('image');
        });
        Schema::create('cart', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->string('session_id')->nullable();
            $t->integer('item_id'); $t->string('item_name'); $t->integer('qty');
            $t->integer('item_type')->default(2); $t->string('item_image')->default('');
            $t->integer('item_price')->default(1000); $t->integer('buynow')->default(0);
            $t->string('tax')->default('');
            foreach (['addons_id','addons_name','addons_price','extras_id','extras_name','extras_price'] as $name) $t->string($name)->nullable();
            $t->integer('addons_total_price')->default(0); $t->integer('extras_total_price')->default(0); $t->timestamps();
        });
        Schema::create('barion_transactions', function (Blueprint $t) {
            $t->id(); $t->integer('order_id'); $t->string('payment_id')->nullable(); $t->text('draft_json')->nullable(); $t->timestamps();
        });
        Schema::create('payment', function (Blueprint $t) { $t->id(); $t->integer('payment_type'); $t->integer('is_activate')->default(1); $t->integer('is_available')->default(1); });
        DB::table('payment')->insert([['payment_type'=>1],['payment_type'=>16]]);
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('timezone');
            foreach (['app_bottom_image','booknow_bg_image','why_choose_image'] as $name) $t->string($name)->nullable();
        });
        DB::table('settings')->insert(['timezone' => 'Europe/Budapest']);
        (require base_path('database/migrations/2026_10_20_000001_create_gift_promotions_table.php'))->up();
        foreach (['Pizzák', 'Palacsinták', 'Italok'] as $i => $name) DB::table('categories')->insert(['id' => $i + 1, 'category_name' => $name, 'slug' => 'category-'.$i]);
        foreach ([[1, 1, 'Sonkás pizza'], [2, 1, 'Sajtos pizza'], [3, 2, 'Kakaós palacsinta'], [4, 2, 'Lekváros palacsinta'], [5, 3, 'Kóla']] as [$id, $category, $name]) {
            DB::table('item')->insert(['id' => $id, 'cat_id' => $category, 'item_name' => $name, 'slug' => 'item-'.$id]);
        }
        Session::start();
        $this->withCredentials()->withCookie(config('session.cookie'), Session::getId());

        DB::table('addons_group')->insert(['id' => 1]);
        DB::table('item')->where('id', 1)->update(['addons_id' => '1']);
        DB::table('addons')->insert(['id' => 11, 'addongroup_id' => 1, 'name' => 'Drink', 'price' => 100]);
        DB::table('extras')->insert(['id' => 21, 'item_id' => 1, 'name' => 'Cheese', 'price' => 230]);
    }

    public function test_catalog_prices_replace_forged_names_and_negative_client_prices(): void
    {
        $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 2, 'addons_id' => '11', 'extras_id' => '21',
            'addons_name' => 'Forged', 'addons_price' => '-900', 'extras_name' => 'Forged', 'extras_price' => '-900'])
            ->assertOk()->assertJson(['status' => 1]);
        $line = Cart::firstOrFail();
        $this->assertSame(100, (int) $line->addons_total_price);
        $this->assertSame(230, (int) $line->extras_total_price);
        $this->assertSame('Cheese', $line->extras_name);
    }

    public function test_unknown_and_foreign_item_extras_are_rejected(): void
    {
        DB::table('extras')->insert(['id' => 22, 'item_id' => 2, 'name' => 'Other item', 'price' => 10]);
        foreach (['99999', '22', '-1'] as $id) {
            $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 1, 'extras_id' => $id])->assertStatus(422);
        }
        $this->assertSame(0, Cart::count());
    }

    public function test_required_addons_cannot_be_bypassed_by_a_direct_cart_request(): void
    {
        DB::table('addons_group')->where('id', 1)->update(['selection_type' => 1, 'min_count' => 1]);
        $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 1])->assertStatus(422);
        $this->assertSame(0, Cart::count());
    }

    public function test_checkout_reprices_an_older_cart_from_the_current_catalog(): void
    {
        $line = new Cart(['item_id' => 1, 'qty' => 2, 'item_price' => 1,
            'addons_id' => '11', 'addons_total_price' => -900, 'extras_id' => '21', 'extras_total_price' => -900]);
        $this->assertSame(2660.0, app(CatalogPricing::class)->refresh(collect([$line])));
        $this->assertSame(1000.0, (float) $line->item_price);
        $this->assertSame(230.0, (float) $line->extras_total_price);
    }

    public function test_forged_total_and_delivery_charge_cannot_bypass_the_cash_delivery_minimum(): void
    {
        Schema::create('shipping_area', function (Blueprint $t) { $t->id(); $t->decimal('delivery_charge'); $t->decimal('min_order')->default(0); });
        DB::table('shipping_area')->insert(['id' => 1, 'delivery_charge' => 760]);
        Cart::create(['item_id' => 1, 'item_name' => 'Pizza', 'qty' => 1, 'item_price' => 1000,
            'session_id' => session()->getId(), 'buynow' => 0]);
        $response = (new CheckoutController())->placeorder(new Request(['order_type' => 1, 'transaction_type' => 1,
            'address' => 'Test street 10', 'city' => 'Test town', 'delivery_area' => 1, 'delivery_charge' => 0,
            'grand_total' => 999999, 'name' => 'Test', 'email' => 'test@example.invalid', 'mobile' => '06200000000', 'terms' => 1, 'buynow' => 0]));
        $this->assertSame('min_order_not_met', $response->getData(true)['code']);
    }

    public function test_unavailable_cart_item_returns_recoverable_checkout_feedback(): void
    {
        Cart::create(['item_id' => 1, 'item_name' => 'Pizza', 'qty' => 1, 'item_price' => 1000,
            'session_id' => session()->getId(), 'buynow' => 0]);
        DB::table('item')->where('id', 1)->update(['today_unavailable' => true]);
        $response = (new CheckoutController())->placeorder(new Request(['order_type' => 2, 'transaction_type' => 1,
            'name' => 'Test', 'email' => 'test@example.invalid', 'mobile' => '06200000000', 'terms' => 1, 'buynow' => 0]));
        $data = $response->getData(true);
        $this->assertSame(0, $data['status']);
        $this->assertSame('cart_changed', $data['code']);
        $this->assertSame(1, Cart::count());
    }

    public function test_gateway_payment_cannot_be_marked_paid_through_the_offline_endpoint(): void
    {
        $response = (new CheckoutController())->placeorder(new Request(['transaction_type' => 16]));
        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_unavailable_payment_is_rejected(): void
    {
        DB::table('payment')->where('payment_type', 1)->update(['is_available' => 0]);
        $this->expectException(ValidationException::class);
        app(CheckoutPricing::class)->requirePayment(1);
    }
}
