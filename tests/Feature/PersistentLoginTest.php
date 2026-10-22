<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\MaintenanceMiddleware;
use App\Http\Middleware\UserMiddleware;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class PersistentLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'persistent_login_test', 'database.connections.persistent_login_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ], 'session.driver' => 'array']);
        $this->withoutMiddleware([MaintenanceMiddleware::class, AdminAuth::class, UserMiddleware::class]);
        Mail::fake();
        Schema::create('manage_roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('modules'); $t->integer('is_available')->default(1); });
        DB::table('manage_roles')->insert(['id' => 1, 'name' => 'Test operator', 'modules' => '1,10']);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password'); $t->integer('type');
            $t->integer('is_available')->default(1); $t->integer('is_verified')->default(1); $t->integer('is_deleted')->default(2);
            $t->integer('role_id')->default(1); $t->integer('user_id')->default(0); $t->string('mobile')->default('06200000000'); $t->string('otp')->nullable();
            $t->rememberToken(); $t->timestamps();
        });
        Schema::create('cart', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->string('session_id')->nullable(); $t->integer('buynow')->default(0);
            $t->timestamps();
        });
        Schema::create('systemaddons', function (Blueprint $t) { $t->id(); $t->string('unique_identifier'); $t->integer('activated'); });
        Schema::create('otp_configuration', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('status'); });
        Route::middleware('web')->get('/__test/persistent-login', fn () => response()->json(['id' => Auth::id(), 'remembered' => Auth::viaRemember()]));
    }

    private function account(int $type, array $values = []): User
    {
        $user = new User();
        $user->forceFill(array_merge(['name' => 'Tartós belépés teszt', 'email' => 'login@example.invalid',
            'password' => Hash::make('test-password'), 'type' => $type], $values))->save();
        return $user->fresh();
    }

    private function login(User $user)
    {
        return $this->post((int) $user->type === 2 ? '/checklogin' : '/admin/check-login',
            ['email' => $user->email, 'password' => 'test-password']);
    }

    /** @dataProvider accountTypes */
    public function test_customer_owner_and_counter_staff_get_a_secure_thirty_day_remember_cookie(int $type): void
    {
        config(['session.secure' => true]);
        $user = $this->account($type);
        $response = $this->login($user)->assertRedirect();
        $name = Auth::guard('web')->getRecallerName();
        $cookie = $response->getCookie($name);
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(now()->getTimestamp() + 30 * 24 * 60 * 60, $cookie->getExpiresTime(), 5);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->assertAuthenticatedAs($user);
    }

    public static function accountTypes(): array
    {
        return ['customer' => [2], 'owner' => [1], 'counter staff' => [4]];
    }

    /** @dataProvider accountTypes */
    public function test_remember_cookie_restores_login_without_the_old_server_session(int $type): void
    {
        $user = $this->account($type);
        $response = $this->login($user);
        $name = Auth::guard('web')->getRecallerName();
        $cookie = $response->getCookie($name);
        $this->assertNotNull($cookie);
        Session::flush(); Auth::forgetGuards();
        $this->withCookie($name, $cookie->getValue())->get('/__test/persistent-login')
            ->assertOk()->assertJson(['id' => $user->id, 'remembered' => true]);
    }

    /** @dataProvider accountTypes */
    public function test_explicit_logout_clears_session_and_revokes_the_old_remember_cookie(int $type): void
    {
        $user = $this->account($type);
        $response = $this->login($user);
        $name = Auth::guard('web')->getRecallerName();
        $cookie = $response->getCookie($name);
        $this->assertNotNull($cookie);
        $sessionId = Session::getId();
        $this->withCookie($name, $cookie->getValue())->get($type === 2 ? '/logout' : '/admin/logout')->assertRedirect()->assertCookieExpired($name);
        $this->assertGuest();
        $this->assertNotSame($sessionId, Session::getId());
        Session::flush(); Auth::forgetGuards();
        $this->withCookie($name, $cookie->getValue())->get('/__test/persistent-login')->assertJson(['id' => null]);
    }

    public function test_verified_otp_login_uses_the_same_thirty_day_cookie(): void
    {
        $user = $this->account(2, ['is_verified' => 2, 'otp' => '123456']);
        DB::table('systemaddons')->insert(['unique_identifier' => 'otp', 'activated' => 1]);
        DB::table('otp_configuration')->insert(['name' => 'local-test', 'status' => 1]);
        $response = $this->withSession(['verification_email' => $user->mobile])->post('/verify-otp', ['otp' => '123456'])->assertRedirect('/');
        $cookie = $response->getCookie(Auth::guard('web')->getRecallerName());
        $this->assertNotNull($cookie);
        $this->assertEqualsWithDelta(now()->getTimestamp() + 30 * 24 * 60 * 60, $cookie->getExpiresTime(), 5);
    }

    /** @dataProvider rejectedAccounts */
    public function test_rejected_accounts_do_not_keep_a_valid_remember_cookie(int $type, int $available): void
    {
        $user = $this->account($type, ['is_available' => $available]);
        // A customer must not gain an admin login; blocked customers/staff must stay logged out.
        $response = $this->post($type === 2 && $available === 0 ? '/checklogin' : '/admin/check-login',
            ['email' => $user->email, 'password' => 'test-password'])->assertRedirect();
        $this->assertGuest();
        $cookie = $response->getCookie(Auth::guard('web')->getRecallerName());
        $this->assertTrue($cookie === null || $cookie->getExpiresTime() < now()->getTimestamp());
    }

    public static function rejectedAccounts(): array { return [[2, 0], [4, 0], [2, 1]]; }
}
