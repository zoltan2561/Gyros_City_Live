<?php

namespace Tests\Feature;

use App\Http\Controllers\front\CheckoutController;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\MaintenanceMiddleware;
use App\Models\BarionTransaction;
use App\Models\Cart;
use App\Models\GiftPromotion;
use App\Models\Item;
use App\Models\User;
use App\Services\BarionService;
use App\Services\GiftPromotions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class GiftPromotionsTest extends TestCase
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
    }

    private function promotion(array $attributes = []): GiftPromotion
    {
        return GiftPromotion::create(array_merge(['name' => 'Két pizza mellé palacsinta', 'category_id' => 1,
            'required_qty' => 2, 'gift_item_ids' => [3, 4], 'gift_qty' => 1, 'repeat' => true, 'active' => true], $attributes));
    }

    private function line(int $item, int $qty, array $attributes = []): Cart
    {
        return Cart::create(array_merge(['item_id' => $item, 'item_name' => Item::findOrFail($item)->item_name,
            'qty' => $qty, 'item_price' => 1000, 'session_id' => Session::getId(), 'buynow' => 0], $attributes));
    }

    public function test_inactive_items_remain_visible_but_cannot_be_ordered(): void
    {
        Item::find(1)->update(['item_status' => 2]);
        Item::find(2)->forceFill(['today_unavailable' => true])->save();
        $this->assertCount(5, Item::visibleOnMenu()->get());
        $this->assertFalse(Item::find(1)->is_orderable);
        $this->assertFalse(Item::find(2)->is_orderable);
        $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 1])->assertOk()->assertJson(['status' => 0]);
        $this->assertSame(0, Cart::count());
        DB::table('categories')->where('id', 1)->update(['is_available' => 0]);
        $this->assertCount(3, Item::visibleOnMenu()->get());
    }

    public function test_unavailable_existing_cart_line_can_be_reduced_but_not_increased(): void
    {
        $line = $this->line(1, 2);
        Item::find(1)->update(['item_status' => 0]);
        $this->postJson('/cart/qtyupdate', ['id' => $line->id, 'type' => 'plus'])->assertOk()->assertJson(['status' => 0]);
        $this->postJson('/cart/qtyupdate', ['id' => $line->id, 'type' => 'minus'])->assertOk()->assertJson(['status' => 1, 'qty' => 1]);
    }

    /** @dataProvider quantities */
    public function test_gift_count_uses_quantities_and_repeat_setting(int $qty, bool $repeat, int $expected): void
    {
        $this->promotion(['repeat' => $repeat]); $this->line(1, $qty);
        $offer = app(GiftPromotions::class)->offers(Cart::all())->first();
        $this->assertSame($expected, $offer['earned']);
    }

    public static function quantities(): array
    {
        return [[1, true, 0], [2, true, 1], [3, true, 1], [4, true, 2], [4, false, 1]];
    }

    public function test_mixed_pizzas_count_together_and_gifts_never_count_as_triggers(): void
    {
        $this->promotion(); $this->line(1, 1); $this->line(2, 1); $this->line(3, 3);
        $this->assertSame(1, app(GiftPromotions::class)->offers(Cart::all())->first()['earned']);
    }

    public function test_specific_item_rule_and_multiple_gifts_are_configurable(): void
    {
        $this->promotion(['category_id' => null, 'trigger_item_ids' => [1], 'gift_qty' => 2]);
        $this->line(1, 2); $this->line(2, 4);
        $this->assertSame(2, app(GiftPromotions::class)->offers(Cart::all())->first()['earned']);
    }

    public function test_choice_is_saved_and_reflected_in_zero_price_order_lines(): void
    {
        $promotion = $this->promotion(); $this->line(1, 2);
        $this->postJson('/cart/promotions', ['promotion_id' => $promotion->id, 'choices' => [4], 'buynow' => 0])
            ->assertOk()->assertJson(['pending' => false]);
        $this->getJson('/cart/promotions')->assertOk()->assertJson(['pending' => false]);
        $gifts = app(GiftPromotions::class)->giftLines(Cart::all());
        $this->assertCount(1, $gifts);
        $this->assertSame('Ajándék: Lekváros palacsinta', $gifts->first()->item_name);
        $this->assertSame(0, $gifts->first()->item_price);
        $this->assertSame(0, $gifts->first()->extras_total_price);
        $this->assertSame(1, Cart::count());
    }

    public function test_unearned_extra_or_non_configured_gifts_are_rejected(): void
    {
        $promotion = $this->promotion(); $line = $this->line(1, 1);
        $data = ['promotion_id' => $promotion->id, 'choices' => [3], 'buynow' => 0];
        $this->postJson('/cart/promotions', $data)->assertStatus(422);
        $line->update(['qty' => 2]);
        $this->postJson('/cart/promotions', array_replace($data, ['choices' => [5]]))->assertStatus(422);
        $this->postJson('/cart/promotions', array_replace($data, ['choices' => [3, 4]]))->assertStatus(422);
    }

    public function test_lowering_quantity_removes_entitlement_and_readding_requires_a_fresh_choice(): void
    {
        $promotion = $this->promotion(); $line = $this->line(1, 4);
        $service = app(GiftPromotions::class);
        $this->assertTrue($service->choose($promotion->id, [3, 4]));
        $line->update(['qty' => 1]);
        $this->assertCount(0, $service->giftLines(Cart::all()));
        $this->assertFalse($service->choose($promotion->id, [3]));
        $line->update(['qty' => 2]);
        $this->assertSame([null], $service->offers(Cart::all())->first()['choices']);
    }

    public function test_paid_cart_item_metadata_and_base_price_come_from_the_catalog(): void
    {
        $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 2, 'item_name' => 'Fake gift',
            'item_type' => 99, 'item_price' => 0, 'tax' => 'fake', 'buynow' => 0])
            ->assertOk()->assertJson(['status' => 1]);
        $this->assertSame('Sonkás pizza', Cart::first()->item_name);
        $this->assertSame(1000, Cart::first()->item_price);
        $this->postJson('/addtocart', ['slug' => 'item-1', 'qty' => 0])->assertStatus(422);
    }

    public function test_a_gift_that_becomes_unavailable_is_invalidated(): void
    {
        $promotion = $this->promotion(); $this->line(1, 2);
        $service = app(GiftPromotions::class);
        $service->choose($promotion->id, [3]);
        Item::find(3)->update(['item_status' => 2]);
        $this->assertNotNull($service->error(Cart::all()));
        $this->assertCount(0, $service->giftLines(Cart::all()));
        $this->assertFalse($service->choose($promotion->id, [3]));
    }

    public function test_other_sessions_users_and_buy_now_cart_do_not_qualify_the_current_cart(): void
    {
        $promotion = $this->promotion();
        $this->line(1, 2, ['session_id' => 'someone-else']);
        $this->line(1, 2, ['user_id' => 999]);
        $this->line(1, 2, ['buynow' => 1]);
        $this->assertSame(0, app(GiftPromotions::class)->offers(app(GiftPromotions::class)->cart())->first()['earned']);
        $this->postJson('/cart/promotions', ['promotion_id' => $promotion->id, 'choices' => [3], 'buynow' => 0])->assertStatus(422);
    }

    public function test_active_dates_include_last_day_and_ignore_disabled_promotions(): void
    {
        $this->travelTo(now('Europe/Budapest')->setDate(2026, 10, 20)->setTime(23, 59));
        $this->promotion(['starts_at' => '2026-10-20', 'ends_at' => '2026-10-20']);
        $this->promotion(['ends_at' => '2026-10-19']);
        $this->promotion(['starts_at' => '2026-10-21']);
        $this->promotion(['active' => false]);
        $this->assertCount(1, app(GiftPromotions::class)->active());
    }

    public function test_admin_can_create_edit_and_deactivate_generic_promotion(): void
    {
        $owner = new User(); $owner->id = 123; $owner->type = 1; $this->actingAs($owner);
        $data = ['name' => 'Két étel mellé ajándék', 'trigger_type' => 'items', 'trigger_item_ids' => [1, 2],
            'required_qty' => 2, 'gift_item_ids' => [3, 4], 'gift_qty' => 1, 'active' => 1, 'ends_at' => '2026-12-31'];
        $this->post('/admin/gift-promotions', $data)->assertRedirect(route('admin.gift-promotions'));
        $this->assertSame([1, 2], GiftPromotion::first()->trigger_item_ids);
        $this->assertTrue(GiftPromotion::first()->active);
        $this->post('/admin/gift-promotions', array_replace($data, ['id' => GiftPromotion::first()->id, 'active' => 0]))->assertRedirect();
        $this->assertSame(1, GiftPromotion::count()); $this->assertFalse(GiftPromotion::first()->active);
        $owner->type = 2;
        $this->post('/admin/gift-promotions', $data)->assertForbidden();
    }

    public function test_cash_and_card_checkout_both_block_missing_gift_choice_and_unavailable_items(): void
    {
        $this->promotion(); $this->line(1, 2);
        $gateway = Mockery::mock(BarionService::class); $gateway->shouldNotReceive('startPayment');
        $this->app->bind(BarionService::class, fn () => $gateway);
        $this->postJson('/barion/indit', ['order_type' => 2, 'grand_total' => 2000, 'buynow' => 0])
            ->assertOk()->assertJson(['ok' => false, 'code' => 'cart_changed']);
        $response = (new CheckoutController())->placeorder(new Request([
            'order_type' => 2, 'transaction_type' => 1, 'grand_total' => 2000, 'buynow' => 0,
        ]));
        $this->assertSame('cart_changed', $response->getData(true)['code']);
        Item::find(1)->update(['item_status' => 0]);
        $this->postJson('/barion/indit', ['order_type' => 2, 'grand_total' => 2000, 'buynow' => 0])
            ->assertOk()->assertJson(['ok' => false, 'code' => 'cart_changed']);
        $this->assertSame(0, BarionTransaction::count());
    }

    public function test_card_draft_includes_the_gift_but_does_not_charge_it(): void
    {
        $promotion = $this->promotion(); $this->line(1, 2);
        app(GiftPromotions::class)->choose($promotion->id, [3]);
        $gateway = Mockery::mock(BarionService::class);
        $gateway->shouldReceive('startPayment')->once()->withArgs(function ($id, $email, $items, $total) {
            $this->assertCount(1, $items); $this->assertSame(2000.0, $total);
            BarionTransaction::create(['order_id' => $id]); return true;
        })->andReturn(['paymentId' => 'gift-test', 'gatewayUrl' => 'https://example.test/pay']);
        $this->app->bind(BarionService::class, fn () => $gateway);
        $this->postJson('/barion/indit', ['order_type' => 2, 'grand_total' => 2000, 'buynow' => 0])->assertOk()->assertJson(['ok' => true]);
        $items = BarionTransaction::first()->draft_json['items'];
        $this->assertCount(2, $items);
        $this->assertSame('Ajándék: Kakaós palacsinta', $items[1]['item_name']);
        $this->assertSame(0, $items[1]['item_price']);
    }
}
