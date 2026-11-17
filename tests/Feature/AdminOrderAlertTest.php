<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
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
        config(['cache.default' => 'array', 'mail.default' => 'array',
            'mail.admin_email' => 'restaurant@example.invalid', 'mail.admin_name' => 'Restaurant']);
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

    public function test_only_old_pending_online_orders_notify_all_four_existing_recipients(): void
    {
        $this->order(1, 1, 1, 'web', now()->subMinutes(9)->toDateTimeString());
        $this->order(2, 2, 1, 'web', now()->subHour()->toDateTimeString());
        $this->order(3, 1, 1, 'pos', now()->subHour()->toDateTimeString());
        $request = Request::create('/admin/order-unprocessed-alert', 'POST', ['count' => 999]);
        $this->assertSame(200, app(NotificationController::class)->unprocessedAlert($request)->getStatusCode());
        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
        DB::table('order')->where('id', 1)->update(['created_at' => now()->subMinutes(10)->toDateTimeString()]);
        $this->assertSame(200, app(NotificationController::class)->unprocessedAlert($request)->getStatusCode());
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $mail = $messages->first()->getOriginalMessage();
        $this->assertSame([
            'admin@gyroscity.eu',
            'blausilverkft@gmail.com',
            'fuleczkikinga23@gmail.com',
            'markdemetet@gmail.com',
        ], array_map(fn ($address) => $address->getAddress(), $mail->getTo()));
        $this->assertStringContainsString('1', $mail->getTextBody());
        $throttled = app(NotificationController::class)->unprocessedAlert($request)->getData(true);
        $this->assertSame('throttled', $throttled['status']);
        $this->assertTrue($throttled['throttled']);
        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_failed_email_does_not_suppress_retry_for_ten_minutes(): void
    {
        $this->order(1, 1, 1, 'web', now()->subHour()->toDateTimeString());
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Synthetic SMTP failure'));
        $response = app(NotificationController::class)->unprocessedAlert(Request::create('/admin/order-unprocessed-alert', 'POST'));
        $this->assertSame(503, $response->getStatusCode());
        $this->assertFalse(Cache::has('unprocessed_order_alert_lock'));
    }

    /** @dataProvider cronScenarios */
    public function test_cron_runs_from_another_directory_without_http_or_an_admin_session(string $scenario, int $exitCode, string $status): void
    {
        $database = tempnam(sys_get_temp_dir(), 'gyros-alert-test-');
        try {
            $pdo = new \PDO('sqlite:' . $database);
            if ($scenario !== 'database_failure') {
                $pdo->exec('CREATE TABLE "order" (id INTEGER PRIMARY KEY, status_type INTEGER, order_from TEXT, created_at TEXT)');
                if ($scenario !== 'idle') {
                    $insert = $pdo->prepare('INSERT INTO "order" VALUES (1, 1, ?, ?)');
                    $insert->execute(['web', now()->subMinutes(11)->toDateTimeString()]);
                }
            }
            $pdo = null;
            $process = new Process([PHP_BINARY, '-d', 'auto_prepend_file=' . base_path('tests/offline-prepend.php'),
                '-d', 'disable_functions=curl_init,curl_exec,fsockopen,pfsockopen,stream_socket_client',
                base_path('cron/decide_unprocessed.php')], sys_get_temp_dir(), [
                    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => $database . '-no-config',
                    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DATABASE_URL' => '',
                    'MAIL_MAILER' => $scenario === 'smtp_failure' ? 'smtp' : 'array',
                    'MAIL_ADMIN_EMAIL' => $scenario === 'no_recipient' ? '' : ($scenario === 'invalid_recipient' ? 'not-an-address' : 'restaurant@example.invalid'),
                    'MAIL_HOST' => '127.0.0.1', 'MAIL_PORT' => '9', 'MAIL_USERNAME' => '', 'MAIL_PASSWORD' => '', 'MAIL_ENCRYPTION' => '',
                    'CACHE_DRIVER' => 'array',
                ]);
            $process->setTimeout(15)->run();
            $this->assertSame($exitCode, $process->getExitCode(), $process->getErrorOutput() . $process->getOutput());
            $this->assertStringContainsString('status=' . $status, $process->getOutput());
            if ($scenario !== 'database_failure') {
                $this->assertStringContainsString('pending=' . ($scenario === 'idle' ? '0' : '1'), $process->getOutput());
            }
        } finally {
            unset($insert, $pdo);
            if (is_file($database)) unlink($database);
        }
    }

    public static function cronScenarios(): array
    {
        return [
            'old pending order' => ['sent', 0, 'sent'],
            'nothing pending' => ['idle', 0, 'idle'],
            'empty global admin contact preserves existing recipients' => ['no_recipient', 0, 'sent'],
            'invalid global admin contact preserves existing recipients' => ['invalid_recipient', 0, 'sent'],
            'SMTP failure without network access' => ['smtp_failure', 1, 'failed'],
            'unexpected database failure' => ['database_failure', 1, 'failed'],
        ];
    }
}
