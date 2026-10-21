<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\AdminController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminOrderAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'order_alert_test', 'database.connections.order_alert_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('order', function (Blueprint $t) {
            $t->id(); $t->integer('status_type'); $t->integer('is_notification');
            $t->string('order_from'); $t->timestamps();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->id(); $t->string('notification_tune');
        });
        DB::table('settings')->insert(['notification_tune' => 'qa-tone.mp3']);
    }

    private function order(int $id, int $status, int $unread, string $source = 'web', string $date = '2026-10-20 12:00:00'): void
    {
        DB::table('order')->insert(['id' => $id, 'status_type' => $status, 'is_notification' => $unread,
            'order_from' => $source, 'created_at' => $date, 'updated_at' => $date]);
    }

    public function test_alerts_follow_acceptance_instead_of_the_read_flag(): void
    {
        $this->order(1, 1, 2);
        $this->order(2, 2, 1);
        $this->order(3, 3, 1);
        $this->order(4, 4, 1);
        $this->order(5, 1, 1, 'pos');
        $data = app(AdminController::class)->getorder()->getData(true);
        $this->assertSame([1], $data['orders']);
        $this->assertSame(1, $data['count']);
        $this->assertSame('qa-tone.mp3', $data['noti']);
    }

    public function test_an_unaccepted_order_stays_pending_after_midnight(): void
    {
        $this->order(1, 1, 1, 'web', '2026-10-19 23:59:00');
        $this->assertSame([1], app(AdminController::class)->getorder()->getData(true)['orders']);
    }

    public function test_acceptance_removes_the_order_from_the_next_poll(): void
    {
        $this->order(1, 1, 1);
        $this->assertSame(1, app(AdminController::class)->getorder()->getData(true)['count']);
        DB::table('order')->where('id', 1)->update(['status_type' => 2]);
        $data = app(AdminController::class)->getorder()->getData(true);
        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['orders']);
    }
}
