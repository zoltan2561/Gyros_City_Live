<?php

namespace Tests\Feature;

use App\Http\Middleware\MaintenanceMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Models\Cart;
use App\Models\GiftPromotion;
use App\Models\User;
use App\Services\GiftPromotions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class GuestCartLoginTest extends TestCase
{
    private User $customer;
    private string $guestSession;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'login_cart_test', 'database.connections.login_cart_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array', 'mail.default' => 'array']);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        $this->withoutMiddleware([MaintenanceMiddleware::class, UserMiddleware::class]);
        Mail::fake();
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
            $t->string('mobile')->default('06200000000'); $t->integer('type')->default(2);
            $t->integer('is_available')->default(1); $t->integer('is_verified')->default(1);
            $t->integer('is_deleted')->default(2); $t->string('otp')->nullable();
            $t->integer('user_id')->default(0); $t->rememberToken(); $t->timestamps();
        });
        Schema::create('cart', function (Blueprint $t) {
            $t->id(); $t->string('session_id')->nullable(); $t->string('user_id')->nullable();
            $t->integer('item_id'); $t->string('item_name'); $t->integer('qty');
            $t->integer('item_type')->default(2); $t->string('item_image')->default('item-test.webp');
            $t->integer('item_price')->default(1000); $t->integer('buynow')->default(0);
            $t->string('tax')->default('');
            foreach (['addons_id', 'addons_name', 'addons_price', 'extras_id', 'extras_name', 'extras_price'] as $name) $t->string($name)->nullable();
            $t->integer('addons_total_price')->default(0); $t->integer('extras_total_price')->default(0); $t->timestamps();
        });
        Schema::create('systemaddons', function (Blueprint $t) { $t->id(); $t->string('unique_identifier'); $t->integer('activated'); });
        Schema::create('otp_configuration', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('status'); });
        Schema::create('categories', function (Blueprint $t) {
            $t->id(); $t->string('category_name'); $t->string('slug'); $t->string('image')->default('');
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        Schema::create('item', function (Blueprint $t) {
            $t->id(); $t->integer('cat_id'); $t->string('item_name'); $t->string('slug');
            $t->integer('item_status')->default(1); $t->boolean('today_unavailable')->default(false);
            $t->integer('item_type')->default(2); $t->integer('price')->default(1000);
        });
        Schema::create('item_images', function (Blueprint $t) { $t->id(); $t->integer('item_id'); $t->string('image'); });
        (require base_path('database/migrations/2026_10_20_000001_create_gift_promotions_table.php'))->up();
        DB::table('categories')->insert(['id' => 1, 'category_name' => 'Pizzák', 'slug' => 'pizzak']);
        foreach ([1 => 'Sonkás pizza', 2 => 'Sajtos pizza', 3 => 'Kakaós palacsinta'] as $id => $name) {
            DB::table('item')->insert(['id' => $id, 'cat_id' => 1, 'item_name' => $name, 'slug' => 'item-'.$id]);
        }
        $this->customer = User::create(['name' => 'Kosár QA', 'email' => 'cart@example.invalid', 'password' => Hash::make('test-password')]);
        $this->customer->refresh();
        Session::start();
        $this->guestSession = Session::getId();
        $this->withSession(['cart_test' => true]);
        $this->withCredentials()->withCookie(config('session.cookie'), $this->guestSession);
    }

    private function line(array $overrides = []): Cart
    {
        return Cart::create(array_merge(['item_id' => 1, 'item_name' => 'Sonkás pizza', 'qty' => 1,
            'session_id' => $this->guestSession, 'user_id' => null, 'buynow' => 0], $overrides))->refresh();
    }

    private function login(string $password = 'test-password')
    {
        $this->withCookie(config('session.cookie'), Session::getId());
        $response = $this->post('/checklogin', ['email' => $this->customer->email, 'password' => $password]);
        $this->withCookie(config('session.cookie'), Session::getId());
        return $response;
    }

    public function test_password_login_keeps_each_customized_line_and_the_existing_account_cart(): void
    {
        $old = $this->line(['user_id' => $this->customer->id, 'session_id' => '', 'item_name' => 'Korábbi pizza']);
        $first = $this->line(['qty' => 2, 'addons_id' => '11', 'addons_name' => 'Üdítő', 'addons_price' => '100', 'addons_total_price' => 100,
            'extras_id' => '21', 'extras_name' => 'Sajt', 'extras_price' => '230', 'extras_total_price' => 230]);
        $second = $this->line(['qty' => 3, 'extras_id' => '22', 'extras_name' => 'Bacon', 'extras_price' => '210', 'extras_total_price' => 210]);
        $other = $this->line(['user_id' => 999, 'item_name' => 'Másik fiók tétele']);
        $differentSession = $this->line(['session_id' => 'another-guest']);
        $this->login()->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->customer);
        $this->assertNotSame($this->guestSession, Session::getId());
        $this->assertEqualsCanonicalizing([$old->id, $first->id, $second->id], Cart::where('user_id', $this->customer->id)->pluck('id')->all());
        foreach ([$first, $second] as $line) {
            $this->assertSame((int) $this->customer->id, (int) $line->fresh()->user_id);
            $this->assertSame('', $line->fresh()->session_id);
            foreach (['qty', 'item_price', 'addons_id', 'addons_name', 'addons_price', 'addons_total_price', 'extras_id', 'extras_name', 'extras_price', 'extras_total_price'] as $field) {
                $this->assertSame($line->$field, $line->fresh()->$field, $field);
            }
        }
        $this->assertSame(999, (int) $other->fresh()->user_id);
        $this->assertNull($differentSession->fresh()->user_id);
        $this->assertSame(5, Cart::count());
    }

    public function test_a_login_without_guest_items_does_not_claim_rows_with_empty_session_ids(): void
    {
        $unowned = $this->line(['session_id' => null]);
        $other = $this->line(['session_id' => null, 'user_id' => 999]);
        $this->withSession(['oldsessionid' => 'another-guest']);
        $stale = $this->line(['session_id' => 'another-guest']);
        $this->login()->assertRedirect();
        $this->assertNull($unowned->fresh()->user_id);
        $this->assertSame(999, (int) $other->fresh()->user_id);
        $this->assertNull($stale->fresh()->user_id);
    }

    public function test_wrong_password_keeps_the_cart_as_guest(): void
    {
        $line = $this->line(['qty' => 2]);
        $this->login('wrong-password')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull($line->fresh()->user_id);
        $this->assertSame(Session::getId(), $line->fresh()->session_id);
    }

    public function test_a_blocked_account_does_not_take_the_cart_or_lose_it_after_session_rotation(): void
    {
        $this->customer->forceFill(['is_available' => 0])->save();
        $line = $this->line(['qty' => 2]);
        $this->login()->assertRedirect();
        $this->assertGuest();
        $this->assertNull($line->fresh()->user_id);
        $this->assertSame(Session::getId(), $line->fresh()->session_id);
    }

    public function test_verified_otp_login_keeps_the_guest_cart(): void
    {
        DB::table('systemaddons')->insert(['unique_identifier' => 'otp', 'activated' => 1]);
        DB::table('otp_configuration')->insert(['name' => 'local-test', 'status' => 1]);
        $this->customer->forceFill(['otp' => '123456', 'is_verified' => 2])->save();
        $this->withSession(['verification_email' => $this->customer->mobile]);
        $line = $this->line(['qty' => 2]);
        $this->post('/verify-otp', ['otp' => '123456'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->customer);
        $this->assertSame((int) $this->customer->id, (int) $line->fresh()->user_id);
        $this->assertSame(2, $line->fresh()->qty);
    }

    public function test_wrong_otp_leaves_the_guest_cart_untouched(): void
    {
        DB::table('systemaddons')->insert(['unique_identifier' => 'otp', 'activated' => 1]);
        DB::table('otp_configuration')->insert(['name' => 'local-test', 'status' => 1]);
        $this->customer->forceFill(['otp' => '123456'])->save();
        $this->withSession(['verification_email' => $this->customer->mobile]);
        $line = $this->line();
        $this->post('/verify-otp', ['otp' => '654321'])->assertRedirect();
        $this->assertGuest();
        $this->assertNull($line->fresh()->user_id);
        $this->assertSame(Session::getId(), $line->fresh()->session_id);
    }

    public function test_the_selected_gift_stays_selected_after_login(): void
    {
        $promotion = GiftPromotion::create(['name' => 'Két pizza mellé ajándék', 'trigger_item_ids' => [1, 2],
            'required_qty' => 2, 'gift_item_ids' => [3], 'gift_qty' => 1, 'repeat' => true, 'active' => true]);
        $this->line(['qty' => 2]);
        $this->postJson('/cart/promotions', ['promotion_id' => $promotion->id, 'choices' => [3], 'buynow' => 0])->assertOk();
        $this->login()->assertRedirect();
        $this->getJson('/cart/promotions')->assertOk()->assertJson(['pending' => false]);
        $gifts = app(GiftPromotions::class);
        $this->assertSame(3, $gifts->giftLines($gifts->cart())->first()?->item_id);
    }

    public function test_current_quick_order_survives_and_an_older_quick_order_is_kept_in_the_regular_cart(): void
    {
        $regular = $this->line(['user_id' => $this->customer->id, 'session_id' => '', 'buynow' => 0]);
        $oldQuick = $this->line(['user_id' => $this->customer->id, 'session_id' => '', 'buynow' => 1, 'item_id' => 2]);
        $newQuick = $this->line(['buynow' => 1, 'qty' => 2]);
        $this->login()->assertRedirect();
        $service = app(GiftPromotions::class);
        $this->assertSame([$newQuick->id], $service->cart(1)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$regular->id, $oldQuick->id], $service->cart(0)->pluck('id')->all());
        $this->assertSame(3, Cart::count());
    }

    public function test_legacy_empty_and_zero_guest_owners_are_transferred(): void
    {
        $empty = $this->line(['user_id' => '']);
        $zero = $this->line(['user_id' => '0']);
        $this->login()->assertRedirect();
        $this->assertSame((int) $this->customer->id, (int) $empty->fresh()->user_id);
        $this->assertSame((int) $this->customer->id, (int) $zero->fresh()->user_id);
    }

    public function test_logout_hides_the_account_cart_and_logging_in_again_does_not_duplicate_it(): void
    {
        $line = $this->line(['qty' => 2]);
        $this->login()->assertRedirect();
        $authenticatedSession = Session::getId();
        // Logged-in additions also carry a session id in the existing cart controller.
        $accountLine = $this->line(['user_id' => $this->customer->id, 'session_id' => $authenticatedSession, 'item_id' => 2]);
        $this->get('/logout')->assertRedirect();
        $this->assertGuest();
        $this->assertNotSame($authenticatedSession, Session::getId());
        $this->assertSame(0, Cart::where('session_id', Session::getId())->count());
        $this->login()->assertRedirect();
        $this->assertEqualsCanonicalizing([$line->id, $accountLine->id], Cart::where('user_id', $this->customer->id)->pluck('id')->all());
        $this->assertSame(2, Cart::count());
    }
}
