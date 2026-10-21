<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\OrderController;
use App\Mail\OrderNotificationMail;
use App\Models\Order;
use App\Services\OrderNotifications;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'order_mail_test', 'database.connections.order_mail_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'mail.default' => 'array']);
        Mail::fake();
        DB::connection()->getPdo()->sqliteCreateFunction('CONCAT', fn (...$parts) => implode('', $parts));
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name')->default('Vásárló'); $t->string('email')->nullable();
            $t->integer('is_mail')->default(1); $t->integer('is_notification')->default(0);
            $t->string('token')->default(''); $t->integer('type')->default(2); $t->timestamps();
        });
        Schema::create('order', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->string('order_number');
            $t->string('name')->default('Teszt Vendég'); $t->string('email')->nullable();
            $t->string('mobile')->nullable(); $t->integer('order_type')->default(1);
            $t->integer('status')->default(1); $t->integer('status_type')->default(1);
            $t->integer('transaction_type')->default(1); $t->string('order_from')->default('web');
            $t->integer('driver_id')->nullable(); $t->decimal('grand_total')->default(6440);
            $t->decimal('delivery_charge')->default(800); $t->decimal('discount_amount')->default(0);
            foreach (['address','city','delivery_date','delivery_time','order_notes'] as $name) $t->string($name)->nullable();
            $t->timestamps();
        });
        Schema::create('order_details', function (Blueprint $t) {
            $t->id(); $t->integer('order_id'); $t->string('item_name'); $t->integer('qty');
            $t->decimal('item_price'); $t->decimal('addons_total_price')->default(0); $t->decimal('extras_total_price')->default(0);
            $t->string('addons_name')->nullable(); $t->string('extras_name')->nullable(); $t->timestamps();
        });
        Schema::create('custom_status', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('type'); $t->integer('order_type');
            $t->integer('is_available')->default(1); $t->integer('is_deleted')->default(2);
        });
        foreach ([[10,'Elfogadott',2,1],[11,'Elkészült',2,1],[13,'Elfogadott',2,2],[12,'Átvehető',2,2],
            [2,'Lezárt',3,1],[5,'Lezárt',3,2],[3,'Törölt',4,1]] as [$id,$name,$type,$orderType]) {
            DB::table('custom_status')->insert(['id'=>$id,'name'=>$name,'type'=>$type,'order_type'=>$orderType]);
        }
        Schema::create('systemaddons', function (Blueprint $t) { $t->id(); $t->string('unique_identifier'); $t->integer('activated'); });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('currency')->default('HUF'); $t->string('email')->nullable();
            foreach (['app_bottom_image','booknow_bg_image','why_choose_image'] as $name) $t->string($name)->nullable();
        });
        DB::table('settings')->insert(['currency'=>'HUF']);
    }

    /** @dataProvider customerStatuses */
    public function test_guests_receive_email_after_a_persisted_status_change(int $status, int $type, int $orderType, string $heading): void
    {
        $order = $this->order(['order_type' => $orderType]);
        $this->assertSame(1, $this->updateStatus($order, $status, $type));
        $this->assertSame($status, $order->fresh()->status);
        Mail::assertSent(OrderNotificationMail::class, function ($mail) use ($order, $heading) {
            $html = $this->renderMail($mail);
            return $mail->hasTo('guest@example.invalid') && str_contains($html, $heading)
                && str_contains($html, $order->order_number);
        });
        Mail::assertSent(OrderNotificationMail::class, 1);
    }

    public static function customerStatuses(): array
    {
        return [[10,2,1,'Rendelésedet elfogadtuk'],[11,2,1,'Az ételed elkészült'],
            [13,2,2,'Rendelésedet elfogadtuk'],[12,2,2,'A rendelésed átvehető'],
            [2,3,1,'Rendelés lezárva'],[5,3,2,'Rendelés lezárva'],[3,4,1,'Rendelés törölve']];
    }

    public function test_repeating_the_same_status_does_not_send_another_email(): void
    {
        $order=$this->order();
        $this->updateStatus($order,10,2);
        $this->updateStatus($order,10,2);
        Mail::assertSent(OrderNotificationMail::class,1);
    }

    public function test_guest_confirmation_contains_saved_items_totals_and_text_alternative(): void
    {
        $order = $this->order(['address' => 'Teszt utca 12.', 'city' => 'Szeged',
            'order_notes' => '<script>alert(1)</script>']);
        $this->assertTrue(app(OrderNotifications::class)->placed($order));
        Mail::assertSent(OrderNotificationMail::class, function ($mail) {
            $html = $this->renderMail($mail);
            $text = view($mail->textView, $mail->buildViewData())->render();
            $this->assertStringContainsString('5 640 HUF', $html);
            $this->assertStringContainsString('6 440 HUF', $html);
            $this->assertStringContainsString('800 HUF', $html);
            $this->assertStringContainsString('Sajt', $html);
            $this->assertStringContainsString('Teszt utca 12.', $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertStringContainsString('GY-EMAIL-1', $text);
            $this->assertStringContainsString('6 440 HUF', $text);
            $this->assertStringContainsString("Kóla\nExtrák: Sajt\n", str_replace("\r\n", "\n", $text));
            $this->assertStringContainsString('GY-EMAIL-1', $mail->subject);
            return $mail->hasTo('guest@example.invalid');
        });
        Mail::assertSent(OrderNotificationMail::class, 1);
    }

    public function test_mailer_builds_both_mime_parts_without_external_delivery(): void
    {
        config(['mail.default' => 'array', 'mail.from.address' => 'orders@example.invalid']);
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $this->assertTrue(app(OrderNotifications::class)->placed($this->order()));
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $message = $messages->first()->getOriginalMessage();
        $this->assertSame('guest@example.invalid', $message->getTo()[0]->getAddress());
        $this->assertStringContainsString('GY-EMAIL-1', $message->getSubject());
        $this->assertStringContainsString('6 440 HUF', $message->getHtmlBody());
        $this->assertStringContainsString('6 440 HUF', $message->getTextBody());
        $this->assertStringContainsString('multipart/alternative', $message->toString());
    }

    /** @dataProvider recipients */
    public function test_order_contact_and_existing_notification_preferences_are_respected(?string $email, ?int $preference, ?string $expected): void
    {
        $userId = $preference === null ? 999 : DB::table('users')->insertGetId([
            'email' => 'profile@example.invalid', 'is_mail' => $preference,
        ]);
        $order = $this->order(['user_id' => $userId, 'email' => $email]);
        $sent = app(OrderNotifications::class)->placed($order);
        $this->assertSame($expected !== null, $sent);
        if ($expected) {
            Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo($expected));
        } else {
            Mail::assertNothingSent();
        }
    }

    public static function recipients(): array
    {
        return [
            'order contact first' => ['checkout@example.invalid', 1, 'checkout@example.invalid'],
            'historical blank contact' => [null, 1, 'profile@example.invalid'],
            'opted out' => ['checkout@example.invalid', 0, null],
            'invalid contact' => ['invalid', 1, null],
            'deleted account with contact' => ['guest@example.invalid', null, 'guest@example.invalid'],
            'deleted account without contact' => [null, null, null],
        ];
    }

    public function test_mail_failure_does_not_undo_the_saved_status(): void
    {
        $order = $this->order();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated SMTP outage'));
        $this->assertSame(1, $this->updateStatus($order, 10, 2));
        $this->assertSame(10, $order->fresh()->status);
    }

    public function test_restaurant_still_receives_a_copy_of_guest_orders(): void
    {
        DB::table('users')->insert(['name' => 'Étterem', 'email' => 'restaurant@example.invalid', 'type' => 1]);
        app(OrderNotifications::class)->placed($this->order());
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('restaurant@example.invalid')
            && $mail->title === 'Új rendelés érkezett' && $mail->summary['name'] === 'Teszt Vendég');
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('guest@example.invalid'));
        Mail::assertSent(OrderNotificationMail::class, 2);
    }

    public function test_pos_orders_do_not_receive_web_order_emails(): void
    {
        DB::table('users')->insert(['name' => 'Étterem', 'email' => 'restaurant@example.invalid', 'type' => 1]);
        $order = $this->order(['order_from' => 'pos']);
        $this->assertFalse(app(OrderNotifications::class)->placed($order));
        Mail::assertNothingSent();
    }

    public function test_other_processing_statuses_use_the_saved_status_name(): void
    {
        DB::table('custom_status')->insert(['id' => 20, 'name' => 'Csomagolás', 'type' => 2, 'order_type' => 1]);
        $order = $this->order();
        $this->updateStatus($order, 20, 2);
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->title === 'Rendelésed állapota: Csomagolás');
    }

    public function test_invalid_status_is_rejected_before_saving_or_sending(): void
    {
        $order = $this->order();
        try {
            $this->updateStatus($order, 12, 2); // Pickup status cannot be used on a delivery.
            $this->fail('Invalid status was accepted.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertSame(1, $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_guest_receives_driver_assignment_once(): void
    {
        $order = $this->order();
        $driverId = DB::table('users')->insertGetId(['name' => 'Teszt futár', 'email' => 'driver@example.invalid', 'type' => 3]);
        $request = new Request(['order_id' => $order->id, 'driver_id' => $driverId]);
        (new OrderController())->assign_driver($request);
        (new OrderController())->assign_driver($request);
        $this->assertSame($driverId, $order->fresh()->driver_id);
        Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('guest@example.invalid')
            && $mail->title === 'Futárhoz került a rendelésed');
        Mail::assertSent(OrderNotificationMail::class, 1);
    }

    /** @dataProvider mailAvailability */
    public function test_guest_cash_checkout_saves_details_and_confirms_even_during_mail_outage(bool $outage): void
    {
        $this->purchaseSchema();
        \Illuminate\Support\Facades\Session::start();
        DB::table('cart')->insert(['session_id' => session()->getId(), 'buynow' => 0,
            'item_id' => 1, 'item_name' => 'Teszt pizza', 'qty' => 2, 'item_price' => 2500,
            'addons_total_price' => 0, 'extras_total_price' => 0]);
        $this->mock(\App\Services\GiftPromotions::class, function ($mock) {
            $mock->shouldReceive('error')->once()->andReturn(null);
            $mock->shouldReceive('giftLines')->once()->andReturn(collect());
        });
        if ($outage) Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Simulated SMTP outage'));
        $response = (new \App\Http\Controllers\front\CheckoutController())->placeorder(new Request([
            'name' => 'Teszt Vendég', 'email' => 'guest@example.invalid', 'mobile' => '0612345678',
            'order_type' => 2, 'transaction_type' => 1, 'grand_total' => 5000,
            'delivery_charge' => 0, 'buynow' => 0,
        ]));
        $this->assertSame(1, $response->getData()->status);
        $this->assertSame(1, Order::count());
        $this->assertNull(Order::first()->user_id);
        $this->assertSame(1, DB::table('order_details')->count());
        $this->assertSame(0, DB::table('cart')->count());
        if (!$outage) {
            Mail::assertSent(OrderNotificationMail::class, fn ($mail) => $mail->hasTo('guest@example.invalid')
                && count($mail->summary['items']) === 1 && $mail->summary['total'] === '5 000 HUF');
        }
    }

    public static function mailAvailability(): array
    {
        return ['available' => [false], 'SMTP outage' => [true]];
    }

    /** @dataProvider paymentStates */
    public function test_barion_sends_confirmation_only_for_successfully_created_order_and_never_on_callback_replay(string $state, int $expected): void
    {
        $this->purchaseSchema();
        DB::table('barion_transactions')->insert(['payment_id' => 'test-payment', 'status' => 'Prepared',
            'draft_json' => json_encode(['order_type' => 2, 'name' => 'Teszt Vendég',
                'email' => 'guest@example.invalid', 'grand_total' => 5000,
                'items' => [['item_id' => 1, 'item_name' => 'Teszt pizza', 'qty' => 2, 'item_price' => 2500]]])]);
        $this->mock(\App\Services\BarionService::class, fn ($mock) =>
            $mock->shouldReceive('fetchState')->twice()->with('test-payment')->andReturn((object) ['Status' => $state]));
        for ($i = 0; $i < 2; $i++) {
            $response = (new \App\Http\Controllers\BarionController())->callback(new Request(['PaymentId' => 'test-payment']));
            $this->assertSame(200, $response->getStatusCode());
        }
        $this->assertSame($expected, Order::count());
        $this->assertSame($expected, DB::table('order_details')->count());
        $this->assertSame(0, DB::table('payment_logs')->where('event', 'CALLBACK_ERROR')->count());
        Mail::assertSent(OrderNotificationMail::class, $expected);
    }

    public static function paymentStates(): array
    {
        return [['Succeeded', 1], ['Failed', 0], ['Cancelled', 0]];
    }

    private function purchaseSchema(): void
    {
        Schema::table('order', function (Blueprint $t) {
            foreach (['order_number_digit', 'order_number_start', 'transaction_id', 'address_type', 'landmark',
                'postal_code', 'country', 'state', 'offer_code', 'tax_amount', 'tax_name', 'payment_status'] as $name) {
                $t->string($name)->nullable();
            }
        });
        Schema::table('order_details', function (Blueprint $t) {
            foreach (['user_id', 'item_id', 'item_type', 'item_image', 'tax', 'addons_id', 'addons_price', 'extras_id', 'extras_price'] as $name) {
                $t->string($name)->nullable();
            }
        });
        Schema::table('settings', function (Blueprint $t) {
            $t->string('timezone')->default('Europe/Budapest');
            $t->integer('order_number_start')->default(1); $t->string('order_prefix')->default('GY-');
        });
        Schema::create('cart', function (Blueprint $t) {
            $t->id(); $t->string('session_id')->nullable(); $t->integer('user_id')->nullable(); $t->integer('buynow')->default(0);
            foreach (['item_id', 'item_name', 'item_type', 'item_image', 'tax', 'qty', 'item_price',
                'addons_id', 'addons_name', 'addons_price', 'addons_total_price', 'extras_id', 'extras_name',
                'extras_price', 'extras_total_price'] as $name) $t->string($name)->nullable();
        });
        Schema::create('barion_transactions', function (Blueprint $t) {
            $t->id(); $t->string('payment_id'); $t->string('status'); $t->text('draft_json')->nullable();
            $t->text('raw_response')->nullable(); $t->timestamp('last_callback_at')->nullable(); $t->timestamps();
        });
        Schema::create('payment_logs', function (Blueprint $t) {
            $t->id(); $t->string('payment_id'); $t->string('event'); $t->text('message'); $t->timestamps();
        });
        DB::table('custom_status')->insert(['id' => 4, 'name' => 'Függőben', 'type' => 1, 'order_type' => 2]);
    }

    private function order(array $attributes = []): Order
    {
        $order=new Order();
        $order->forceFill(array_merge(['order_number'=>'GY-EMAIL-1','name'=>'Teszt Vendég','email'=>'guest@example.invalid'],$attributes))->save();
        DB::table('order_details')->insert(['order_id'=>$order->id,'item_name'=>'Sonkás pizza','qty'=>2,
            'item_price'=>2490,'addons_total_price'=>100,'extras_total_price'=>230,'addons_name'=>'Kóla','extras_name'=>'Sajt']);
        return $order->fresh();
    }

    private function updateStatus(Order $order, int $status, int $type)
    {
        return (new OrderController())->update(new Request(['id'=>$order->id,'status'=>$status,'statustype'=>$type]));
    }

    private function renderMail(OrderNotificationMail $mail): string
    {
        $mail->build();
        return view($mail->view, $mail->buildViewData())->render();
    }
}
