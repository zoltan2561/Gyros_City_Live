<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\TimeController;
use App\Http\Controllers\BarionController;
use App\Http\Controllers\front\CheckoutController;
use App\Services\OnlineOrderingHours;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnlineOrderingHoursTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'online_hours_test','database.connections.online_hours_test'=>[
            'driver'=>'sqlite','database'=>':memory:','prefix'=>'',
        ]]);
        Schema::create('users', function (Blueprint $t) { $t->id();$t->integer('type');$t->integer('is_online'); });
        DB::table('users')->insert(['type'=>1,'is_online'=>1]);
        Schema::create('settings', function (Blueprint $t) {
            $t->id();$t->string('timezone');$t->integer('interval_time')->default(30);$t->integer('interval_type')->default(1);
            $t->integer('perslot_booking_limit')->default(10);$t->integer('ordertype_date_time')->nullable();$t->timestamps();
        });
        DB::table('settings')->insert(['timezone'=>'Europe/Budapest']);
        Schema::create('time', function (Blueprint $t) {
            $t->id();$t->string('day');$t->string('open_time');$t->string('close_time');
            $t->string('break_start');$t->string('break_end');$t->integer('always_close')->default(2);$t->timestamps();
        });
        foreach ($this->days() as $day) DB::table('time')->insert(['day'=>$day,'open_time'=>'8:00 AM','close_time'=>'10:00 PM','break_start'=>'12:00pm','break_end'=>'12:00 PM']);
        $this->migration()->up();
    }

    private function days(): array { return ['monday','tuesday','wednesday','thursday','friday','saturday','sunday']; }
    private function migration() { return require base_path('database/migrations/2026_10_22_000002_add_online_order_until_to_time.php'); }
    private function allowsAt(string $when): bool { return app(OnlineOrderingHours::class)->withinSchedule(CarbonImmutable::parse($when,'Europe/Budapest')); }

    /** @dataProvider boundaries */
    public function test_default_deadline_boundaries(string $time, bool $allowed): void
    {
        $this->assertSame($allowed,$this->allowsAt('2026-10-22 '.$time));
    }
    public static function boundaries(): array { return [['07:59:59',false],['08:00:00',true],['12:00:00',true],['21:29:59',true],['21:30:00',false],['22:00:00',false]]; }

    public function test_each_day_uses_its_own_deadline_and_closed_days_stop_online_ordering(): void
    {
        DB::table('time')->where('day','thursday')->update(['online_order_until'=>'20:15']);
        $this->assertTrue($this->allowsAt('2026-10-22 20:14:59'));
        $this->assertFalse($this->allowsAt('2026-10-22 20:15:00'));
        $this->assertTrue($this->allowsAt('2026-10-23 20:30'));
        DB::table('time')->where('day','thursday')->update(['always_close'=>1]);
        $this->assertFalse($this->allowsAt('2026-10-22 12:00'));
    }

    public function test_breaks_are_respected_but_equal_break_endpoints_do_not_close_the_restaurant(): void
    {
        $this->assertTrue($this->allowsAt('2026-10-22 12:00'));
        DB::table('time')->where('day','thursday')->update(['break_start'=>'12:30 PM','break_end'=>'1:00pm']);
        $this->assertTrue($this->allowsAt('2026-10-22 12:29:59'));
        $this->assertFalse($this->allowsAt('2026-10-22 12:30'));
        $this->assertFalse($this->allowsAt('2026-10-22 12:59:59'));
        $this->assertTrue($this->allowsAt('2026-10-22 13:00'));
    }

    public function test_overnight_deadline_belongs_to_the_previous_open_day(): void
    {
        DB::table('time')->where('day','thursday')->update(['open_time'=>'6:00pm','close_time'=>'02:00','online_order_until'=>'01:30']);
        DB::table('time')->where('day','friday')->update(['always_close'=>1]);
        $this->assertTrue($this->allowsAt('2026-10-22 18:00'));
        $this->assertTrue($this->allowsAt('2026-10-23 01:29:59'));
        $this->assertFalse($this->allowsAt('2026-10-23 01:30'));
    }

    public function test_the_restaurant_switch_and_local_timezone_are_enforced(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 19:00','UTC')); // 21:00 local.
        $this->assertTrue(app(OnlineOrderingHours::class)->accepting());
        DB::table('users')->where('type',1)->update(['is_online'=>2]);
        DB::table('users')->insert(['type'=>4,'is_online'=>1]);
        $this->assertFalse(app(OnlineOrderingHours::class)->accepting());
        DB::table('users')->where('type',1)->update(['is_online'=>1]);
        $this->travelTo(CarbonImmutable::parse('2026-10-22 19:30','UTC'));
        $this->assertFalse(app(OnlineOrderingHours::class)->accepting());
    }

    public function test_closing_is_enforced_for_the_cart_cash_checkout_and_card_payment_start(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 21:30','Europe/Budapest'));
        $this->assertSame(0,(new CheckoutController())->isopenclose(new Request())->getData()->status);
        $cash=(new CheckoutController())->placeorder(new Request(['transaction_type'=>1,'order_type'=>2,
            'name'=>'QA Customer','email'=>'qa@example.invalid','mobile'=>'000000000','terms'=>1]));
        $this->assertSame(0,$cash->getData()->status);
        $this->assertSame(trans('messages.restaurant_closed'),$cash->getData()->message);
        $card=(new BarionController())->start(new Request());
        $this->assertFalse($card->getData()->ok);
        $this->assertSame(trans('messages.restaurant_closed'),$card->getData()->msg);
    }

    private function payload(): array
    {
        return ['day'=>$this->days(),'open_time'=>array_fill(0,7,'8:00 AM'),'close_time'=>array_fill(0,7,'10:00 PM'),
            'break_start'=>array_fill(0,7,'12:00 PM'),'break_end'=>array_fill(0,7,'12:00 PM'),'always_close'=>array_fill(0,7,2),
            'online_order_until'=>array_fill(0,7,'20:15'),'interval_time'=>30,'interval_type'=>1,'slot_limit'=>10,'ordertypedatetime'=>1];
    }

    public function test_admin_can_save_daily_cutoffs_and_the_next_order_check_reads_them(): void
    {
        (new TimeController())->store(new Request($this->payload()));
        $this->assertSame(['20:15'],DB::table('time')->pluck('online_order_until')->unique()->values()->all());
        $this->assertTrue($this->allowsAt('2026-10-22 20:14:59'));
        $this->assertFalse($this->allowsAt('2026-10-22 20:15'));
    }

    public function test_invalid_deadline_does_not_partly_save_opening_hours_or_settings(): void
    {
        $payload=$this->payload();$payload['online_order_until'][3]='23:00';$payload['interval_time']=999;
        try {(new TimeController())->store(new Request($payload));$this->fail('Invalid cutoff accepted');}
        catch (\Illuminate\Validation\ValidationException $e) {$this->assertArrayHasKey('online_order_until.3',$e->errors());}
        $this->assertSame(30,DB::table('settings')->value('interval_time'));
        $this->assertSame(['21:30'],DB::table('time')->pluck('online_order_until')->unique()->values()->all());
    }

    public function test_additive_migration_keeps_the_old_data_and_legacy_code_window_until_migrated(): void
    {
        $before=DB::table('time')->get(['id','day','open_time','close_time','always_close'])->toJson();
        $this->migration()->down();
        $this->assertTrue($this->allowsAt('2026-10-22 21:29'));
        $this->assertFalse($this->allowsAt('2026-10-22 21:30'));
        $this->migration()->up();$this->migration()->up();
        $this->assertSame($before,DB::table('time')->get(['id','day','open_time','close_time','always_close'])->toJson());
        $this->assertSame(['21:30'],DB::table('time')->pluck('online_order_until')->unique()->values()->all());
    }

    public function test_public_hours_show_the_saved_cutoff_only_for_open_days(): void
    {
        $this->app->setLocale('hu');
        $hours = (object) ['always_close'=>2,'online_order_until'=>'20:15'];
        $html = view('web.partials.online-order-deadline',compact('hours'))->render();
        $this->assertStringContainsString('Online rendelés eddig: 20:15',$html);
        $hours->always_close=1;
        $this->assertSame('',trim(view('web.partials.online-order-deadline',compact('hours'))->render()));
    }
}
