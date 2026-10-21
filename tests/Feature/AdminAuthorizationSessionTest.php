<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminAuth;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AdminAuthorizationSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
            'database.default' => 'admin_session_test',
            'database.connections.admin_session_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
            'session.driver' => 'array',
        ]);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('type');
            $table->rememberToken();
        });
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->integer('is_available');
            $table->integer('is_deleted');
            $table->integer('is_default')->default(1);
            $table->string('name')->default('Hungarian');
            $table->string('code')->default('hu');
            $table->string('image')->default('');
            $table->integer('layout')->default(1);
        });
        DB::table('languages')->insert(['is_available' => 1, 'is_deleted' => 2]);
        Route::middleware(['web', AdminAuth::class])->get('/_test/admin-session', fn () => response()->json(['admin' => true]));
        Route::middleware(['web', 'auth'])->get('/_test/customer-session', fn () => response()->json(['user_id' => Auth::id()]));
    }

    public function test_admin_polling_denies_customers_without_logging_them_out(): void
    {
        $user = $this->login(2);
        // Reproduce the background request sent by an admin tab left open during customer login.
        $this->get('/admin/getorder')->assertRedirect(url('admin'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($user->id, Session::get(Auth::guard()->getName()));
        $this->withCookie(config('session.cookie'), Session::getId());
        Auth::forgetGuards();
        $this->get('/_test/customer-session')->assertOk()->assertJson(['user_id' => $user->id]);
    }

    /** @dataProvider adminTypes */
    public function test_admin_and_staff_still_have_access(int $type): void
    {
        $this->login($type);
        $this->get('/_test/admin-session')->assertOk()->assertJson(['admin' => true]);
    }

    public static function adminTypes(): array
    {
        return [[1], [4]];
    }

    public function test_guests_still_cannot_access_admin_routes(): void
    {
        $this->get('/_test/admin-session')->assertRedirect(url('admin'));
        $this->assertGuest();
    }

    private function login(int $type): User
    {
        $id = DB::table('users')->insertGetId(['type' => $type]);
        $user = User::findOrFail($id);
        Session::start();
        Auth::login($user);
        Session::save();
        $this->withCookie(config('session.cookie'), Session::getId());
        Auth::forgetGuards();
        return $user;
    }
}
