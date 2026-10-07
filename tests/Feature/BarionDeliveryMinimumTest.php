<?php

namespace Tests\Feature;

use App\Models\BarionTransaction;
use App\Models\Cart;
use App\Models\User;
use App\Http\Controllers\front\CheckoutController;
use App\Services\BarionService;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class BarionDeliveryMinimumTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // A separate in-memory connection; never use the application's configured database.
        config([
            'database.default' => 'delivery_minimum_test',
            'database.connections.delivery_minimum_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
            'session.driver' => 'array',
        ]);

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->integer('buynow')->default(0);
            $table->integer('item_id');
            $table->string('item_name');
            $table->integer('qty');
            $table->decimal('item_price', 10, 2);
            $table->timestamps();
        });
        Schema::create('barion_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('payment_id')->nullable();
            $table->text('draft_json')->nullable();
            $table->timestamps();
        });
    }

    /** @dataProvider underMinimumOrders */
    public function test_below_minimum_is_rejected_before_any_payment($charge, $total, $loggedIn, $buynow): void
    {
        $this->seedCart($loggedIn, $buynow, $total - $charge);
        $gateway = Mockery::mock(BarionService::class);
        $gateway->shouldNotReceive('startPayment');
        $this->app->bind(BarionService::class, fn () => $gateway);

        $this->postJson('/barion/indit', [
            'order_type' => 1,
            'delivery_charge' => $charge,
            'grand_total' => $total,
            'buynow' => $buynow,
        ])->assertOk()->assertJson([
            'ok' => false, 'code' => 'min_order_not_met',
        ])->assertJsonMissingPath('redirect');

        $this->assertSame(0, BarionTransaction::count());
        $this->assertSame(1, Cart::count());
        $this->assertFalse(Session::has('payment_type'));
    }

    public static function underMinimumOrders(): array
    {
        $orders = [];
        foreach ([false, true] as $loggedIn) {
            foreach ([0, 1] as $buynow) {
                foreach ([[760, 6659], [2200, 10099], [2400, 14299]] as [$charge, $total]) {
                    $orders[] = [$charge, $total, $loggedIn, $buynow];
                }
            }
        }
        return $orders;
    }

    /** @dataProvider acceptedOrders */
    public function test_valid_orders_can_start_payment_and_store_the_draft($type, $charge, $total): void
    {
        $this->seedCart(false, 0, $type === 1 ? $total - $charge : $total);
        $gateway = Mockery::mock(BarionService::class);
        $gateway->shouldReceive('startPayment')->once()->withArgs(function ($id, $email, $items, $amount) use ($total) {
            $this->assertSame((float) $total, $amount);
            BarionTransaction::create(['order_id' => $id]);
            return true;
        })->andReturn([
            'paymentId' => 'test-payment', 'gatewayUrl' => 'https://example.test/pay',
        ]);
        $this->app->bind(BarionService::class, fn () => $gateway);

        $this->postJson('/barion/indit', [
            'order_type' => $type,
            'delivery_charge' => $charge,
            'grand_total' => $total,
        ])->assertOk()->assertJson([
            'ok' => true, 'redirect' => 'https://example.test/pay',
        ]);

        $this->assertSame(1, BarionTransaction::count());
        $this->assertSame((string) $total, BarionTransaction::first()->draft_json['grand_total']);
        $this->assertSame(1, Cart::count());
    }

    public static function acceptedOrders(): array
    {
        return [
            'nearby' => [1, 560, 660],
            'second band exact' => [1, 760, 6660],
            'second band above' => [1, 760, 6661],
            'third band exact' => [1, 2200, 10100],
            'third band above' => [1, 2200, 10101],
            'fourth band exact' => [1, 2400, 14300],
            'fourth band above' => [1, 2400, 14301],
            'pickup' => [2, 0, 500],
        ];
    }

    public function test_cash_checkout_still_rejects_the_same_delivery_minimum(): void
    {
        $this->seedCart(false, 0, 5899);
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('timezone');
            $table->string('app_bottom_image')->nullable();
            $table->string('booknow_bg_image')->nullable();
            $table->string('why_choose_image')->nullable();
        });
        DB::table('settings')->insert(['timezone' => 'Europe/Budapest']);
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));

        $response = (new CheckoutController())->placeorder(new Request([
            'order_type' => 1,
            'transaction_type' => 1,
            'address' => 'Test utca 10',
            'city' => 'Budapest',
            'delivery_charge' => 760,
            'grand_total' => 6659,
            'buynow' => 0,
        ]));

        $this->assertSame(0, $response->getData(true)['status']);
        $this->assertSame('min_order_not_met', $response->getData(true)['code']);
        $this->assertSame(1, Cart::count());
    }

    private function seedCart(bool $loggedIn, int $buynow, int $amount): void
    {
        Session::start();
        $this->withCredentials();
        $this->withCookie(config('session.cookie'), Session::getId());
        if ($loggedIn) {
            $user = new User();
            $user->id = 123;
            $user->type = 2;
            $this->actingAs($user);
        }
        Cart::create([
            'session_id' => $loggedIn ? null : Session::getId(),
            'user_id' => $loggedIn ? 123 : null,
            'buynow' => $buynow,
            'item_id' => 1,
            'item_name' => 'Test gyros',
            'qty' => 1,
            'item_price' => $amount,
        ]);
    }
}
