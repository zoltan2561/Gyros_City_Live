<?php

namespace Tests\Feature;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\NewsletterDelivery;
use App\Models\Subscribe;
use App\Services\NewsletterSender;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\MaintenanceMiddleware;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class NewsletterPortTest extends TestCase
{
    protected $owner;
    protected $cookies = [];
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'newsletter_test', 'database.connections.newsletter_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'session.driver' => 'array', 'mail.default' => 'array']);
        app()->setLocale('hu');
        Mail::fake();
        $this->withoutMiddleware([AdminAuth::class, MaintenanceMiddleware::class]);
        // Keep real CSRF validation active even when Laravel runs in its testing environment.
        $this->app->bind(VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->integer('type'); $table->integer('is_available')->default(1); $table->integer('is_deleted')->default(2); $table->rememberToken(); $table->timestamps();
        });
        (require base_path('database/migrations/2023_08_10_043354_create_subscribe_table.php'))->up();
        (require base_path('database/migrations/2026_10_07_000001_create_newsletter_tables.php'))->up();
        // The shared legacy admin shell is checked in a browser against the isolated MariaDB QA app.
        View::getFinder()->prependLocation(base_path('tests/Fixtures/newsletter/views'));
        $this->owner = $this->user(1);
        $this->send('GET', '/admin/newsletter', [], $this->owner);
    }

    private function user(int $type): User
    {
        return User::create(['name' => 'Newsletter QA', 'email' => 'user-'.$type.'-'.uniqid().'@example.invalid',
            'password' => 'unused-test-hash', 'type' => $type]);
    }

    private function send(string $method, string $uri, array $data = [], ?User $user = null)
    {
        if ($method !== 'GET' && !array_key_exists('_token', $data)) $data['_token'] = $this->token;
        $request = Request::create('http://localhost'.$uri, $method, $data, $this->cookies, [],
            ['HTTP_ACCEPT' => 'application/json', 'HTTP_REFERER' => 'http://localhost/admin/newsletter']);
        $this->app->instance('request', $request);
        Auth::forgetGuards();
        if ($user) Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user ?? Auth::guard('web')->user());
        $kernel = $this->app->make(Kernel::class);
        $response = $kernel->handle($request);
        foreach ($response->headers->getCookies() as $cookie) $this->cookies[$cookie->getName()] = $cookie->getValue();
        if ($request->hasSession()) $this->token = $request->session()->token();
        $kernel->terminate($request, $response);
        return $response;
    }

    private function draft(): Newsletter
    {
        return Newsletter::create(['subject' => 'Őszi ajánlatok', 'body' => "Kedves Vendég!\n\nVárunk szeretettel.", 'created_by' => $this->owner->id]);
    }

    public function test_draft_is_saved_editable_and_visible_in_admin(): void
    {
        $this->assertSame(200, $this->send('GET', '/admin/newsletter/create', [], $this->owner)->getStatusCode());
        $response = $this->send('POST', '/admin/newsletter', ['subject' => 'Új ajánlat', 'body' => 'Kedves Vendég!'], $this->owner);
        $this->assertSame(302, $response->getStatusCode());
        $letter = Newsletter::firstOrFail();
        $this->assertSame($this->owner->id, (int) $letter->created_by);
        $this->assertSame(302, $this->send('POST', '/admin/newsletter/' . $letter->id . '/update', ['subject' => 'Friss ajánlat', 'body' => 'Módosított levél'], $this->owner)->getStatusCode());
        $this->assertSame('Módosított levél', $letter->fresh()->body);
        foreach (['/admin/newsletter', '/admin/newsletter/' . $letter->id . '/edit'] as $uri) {
            $page = $this->send('GET', $uri, [], $this->owner);
            $this->assertSame(200, $page->getStatusCode());
            $this->assertStringContainsString('Friss ajánlat', $page->getContent());
        }
        Mail::assertNothingSent();
    }

    public function test_only_owner_can_use_every_admin_action(): void
    {
        $letter = $this->draft();
        $employee = $this->user(4);
        foreach ([['GET', '/admin/newsletter'], ['GET', '/admin/newsletter/create'], ['POST', '/admin/newsletter'],
            ['POST', '/admin/newsletter/test'], ['GET', '/admin/newsletter/' . $letter->id . '/edit'],
            ['POST', '/admin/newsletter/' . $letter->id . '/update'], ['POST', '/admin/newsletter/' . $letter->id . '/send'],
            ['GET', '/admin/newsletter/' . $letter->id], ['POST', '/admin/newsletter/' . $letter->id . '/process']] as [$method, $uri]) {
            $this->assertSame(403, $this->send($method, $uri, ['subject' => 'Forbidden', 'body' => 'No', 'confirm' => 1], $employee)->getStatusCode(), $uri);
        }
        $this->assertSame(403, $this->send('GET', '/admin/newsletter', [], $this->user(2))->getStatusCode());
        $this->assertSame('draft', $letter->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_validation_csrf_and_confirmation_reject_without_sending(): void
    {
        $letter = $this->draft();
        Subscribe::create(['email' => 'guest@example.invalid']);
        $this->assertSame(419, $this->send('POST', '/admin/newsletter/' . $letter->id . '/send', ['confirm' => 1, '_token' => 'invalid'], $this->owner)->getStatusCode());
        $this->assertSame(422, $this->send('POST', '/admin/newsletter/' . $letter->id . '/send', [], $this->owner)->getStatusCode());
        foreach ([['subject' => "Bad\r\nBcc: stranger@example.invalid", 'body' => 'Hello'], ['subject' => 'Hello', 'body' => '   '],
            ['subject' => str_repeat('a', 181), 'body' => 'Hello'], ['subject' => 'Hello', 'body' => str_repeat('a', 20001)]] as $data) {
            $this->assertSame(422, $this->send('POST', '/admin/newsletter', $data, $this->owner)->getStatusCode());
        }
        $this->assertSame(1, Newsletter::count());
        $this->assertSame(0, NewsletterDelivery::count());
        Mail::assertNothingSent();
    }

    public function test_test_email_only_uses_the_requested_address(): void
    {
        Subscribe::create(['email' => 'subscriber@example.invalid']);
        $response = $this->send('POST', '/admin/newsletter/test', ['subject' => 'Próba', 'body' => 'Új ajánlat', 'test_email' => 'tester@example.invalid'], $this->owner);
        $this->assertSame(200, $response->getStatusCode());
        Mail::assertSent(NewsletterMail::class, fn ($mail) => $mail->hasTo('tester@example.invalid') && $mail->unsubscribeUrl === null);
        Mail::assertSent(NewsletterMail::class, 1);
        $this->assertSame(0, NewsletterDelivery::count());
    }

    public function test_batches_deduplicate_skip_bad_addresses_and_never_resend(): void
    {
        foreach ([' A@example.invalid ', 'a@EXAMPLE.invalid', 'broken', '', 'b@example.invalid', 'c@example.invalid',
            'd@example.invalid', 'e@example.invalid', 'f@example.invalid', 'g@example.invalid'] as $email) Subscribe::create(['email' => $email]);
        $sender = app(NewsletterSender::class);
        $this->assertSame(7, $sender->recipientCount());
        $letter = $this->draft();
        $uri = '/admin/newsletter/' . $letter->id;
        $this->assertSame(302, $this->send('POST', $uri . '/send', ['confirm' => 1], $this->owner)->getStatusCode());
        $this->assertSame(7, $letter->deliveries()->count());
        $this->assertSame(409, $this->send('POST', $uri . '/send', ['confirm' => 1], $this->owner)->getStatusCode());
        $this->assertSame(409, $this->send('POST', $uri . '/update', ['subject' => 'Changed', 'body' => 'Changed'], $this->owner)->getStatusCode());
        $this->assertSame(200, $this->send('POST', $uri . '/process', [], $this->owner)->getStatusCode());
        Mail::assertSent(NewsletterMail::class, 5);
        $this->assertSame(2, $letter->progress()['pending']);
        $this->assertSame(200, $this->send('GET', $uri, [], $this->owner)->getStatusCode());
        $this->assertSame(200, $this->send('POST', $uri . '/process', [], $this->owner)->getStatusCode());
        Mail::assertSent(NewsletterMail::class, 7);
        $this->assertSame('completed', $letter->fresh()->status);
        $this->send('POST', $uri . '/process', [], $this->owner);
        Mail::assertSent(NewsletterMail::class, 7);
        Mail::assertSent(NewsletterMail::class, fn ($mail) => count($mail->to) === 1 && !$mail->cc && !$mail->bcc && str_contains($mail->unsubscribeUrl, '/newsletter/unsubscribe/'));
    }

    public function test_no_subscribers_keeps_the_letter_as_a_draft(): void
    {
        Subscribe::create(['email' => 'broken']);
        $letter = $this->draft();
        $this->assertSame(422, $this->send('POST', '/admin/newsletter/' . $letter->id . '/send', ['confirm' => 1], $this->owner)->getStatusCode());
        $this->assertSame('draft', $letter->fresh()->status);
        $this->assertSame(0, NewsletterDelivery::count());
    }

    public function test_unsubscribe_is_confirmed_and_stops_pending_messages(): void
    {
        Subscribe::create(['email' => 'guest@example.invalid']);
        Subscribe::create(['email' => ' GUEST@example.invalid ']);
        $letter = $this->draft();
        app(NewsletterSender::class)->start($letter);
        $delivery = $letter->deliveries()->firstOrFail();
        $uri = '/newsletter/unsubscribe/' . $delivery->token;
        $this->assertSame(200, $this->send('GET', $uri)->getStatusCode());
        $this->assertSame(2, Subscribe::count(), 'Email link scanners must not unsubscribe on GET.');
        $this->assertSame(419, $this->send('POST', $uri, ['_token' => 'invalid'])->getStatusCode());
        $this->assertSame(200, $this->send('POST', $uri)->getStatusCode());
        $this->assertSame(0, Subscribe::count());
        $this->assertSame('skipped', $delivery->fresh()->status);
        $this->assertSame(200, $this->send('POST', $uri)->getStatusCode());
        app(NewsletterSender::class)->process($letter);
        Mail::assertNothingSent();
        $this->assertSame(404, $this->send('GET', '/newsletter/unsubscribe/' . str_repeat('a', 64))->getStatusCode());
    }

    public function test_smtp_failure_is_recorded_and_not_retried(): void
    {
        Subscribe::create(['email' => 'guest@example.invalid']);
        $letter = $this->draft();
        $sender = app(NewsletterSender::class);
        $sender->start($letter);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Synthetic SMTP rejection'));
        $this->assertSame(1, $sender->process($letter)['failed']);
        $this->assertSame(1, $sender->process($letter)['failed']);
        $this->assertSame('completed', $letter->fresh()->status);
    }

    public function test_cron_command_has_a_bounded_batch(): void
    {
        foreach (range(1, 4) as $id) Subscribe::create(['email' => 'guest' . $id . '@example.invalid']);
        $letter = $this->draft();
        app(NewsletterSender::class)->start($letter);
        $this->assertSame(0, Artisan::call('newsletter:send', ['--limit' => 2]));
        Mail::assertSent(NewsletterMail::class, 2);
        $this->assertSame(2, $letter->progress()['pending']);
        Artisan::call('newsletter:send', ['--limit' => 2]);
        Mail::assertSent(NewsletterMail::class, 4);
    }

    public function test_an_already_claimed_recipient_is_not_sent_again(): void
    {
        Subscribe::create(['email' => 'guest@example.invalid']);
        $letter = $this->draft();
        $sender = app(NewsletterSender::class);
        $sender->start($letter);
        $letter->deliveries()->update(['status' => 'sending']);
        $progress = $sender->process($letter);
        $this->assertSame(1, $progress['sending']);
        $this->assertFalse($progress['finished']);
        $this->assertSame('sending', $letter->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_one_failed_recipient_does_not_block_the_next(): void
    {
        foreach (['bad@example.invalid', 'good@example.invalid'] as $email) Subscribe::create(['email' => $email]);
        $letter = $this->draft();
        $sender = app(NewsletterSender::class);
        $sender->start($letter);
        $pending = \Mockery::mock(\Illuminate\Mail\PendingMail::class);
        $pending->shouldReceive('send')->once()->with(\Mockery::type(NewsletterMail::class))->andReturnNull();
        Mail::shouldReceive('to')->with('bad@example.invalid')->once()->andThrow(new \RuntimeException('Synthetic rejection'));
        Mail::shouldReceive('to')->with('good@example.invalid')->once()->andReturn($pending);
        $progress = $sender->process($letter);
        $this->assertSame(1, $progress['failed']);
        $this->assertSame(1, $progress['sent']);
        $this->assertTrue($progress['finished']);
        \Mockery::close();
    }

    public function test_email_text_is_escaped_and_has_unsubscribe_footer(): void
    {
        $mail = new NewsletterMail('Őszi <ajánlat>', "<script>alert(1)</script>\nÚj menü", 'http://localhost/newsletter/unsubscribe/' . str_repeat('a', 64));
        // This Laravel patch level delegates render() through the mail factory, unlike FoodPRO's test runtime.
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $rendered = $mail->render();
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringContainsString('Új menü', $rendered);
        $this->assertStringContainsString($mail->unsubscribeUrl, $rendered);
    }

    public function test_public_subscribe_validates_and_deduplicates(): void
    {
        $this->assertSame(422, $this->send('POST', '/subscribe', ['subscribe_email' => 'broken'])->getStatusCode());
        $this->assertSame(0, Subscribe::count());
        foreach (['Guest@example.invalid', 'guest@EXAMPLE.invalid'] as $email) {
            $this->assertSame(302, $this->send('POST', '/subscribe', ['subscribe_email' => $email])->getStatusCode());
        }
        $this->assertSame(1, Subscribe::count());
        $this->assertSame('guest@example.invalid', Subscribe::first()->email);
    }
}
