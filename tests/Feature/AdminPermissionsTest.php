<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\AdminAuth;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class AdminPermissionsTest extends \Tests\TestCase
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
        Schema::create('manage_roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('modules'); $t->integer('is_available')->default(1); });
        DB::table('manage_roles')->insert(['id' => 1, 'name' => 'Test operator', 'modules' => '1,10']);
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('type'); $table->integer('is_available')->default(1); $table->integer('is_deleted')->default(2); $table->integer('role_id')->default(1);
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
        $this->partialMock(\App\Http\Controllers\admin\OrderController::class, fn ($mock) => $mock->shouldReceive('index')->andReturn(response()->json(['admin' => true])));
        Route::middleware(['web', AdminAuth::class])->get('/_test/admin-session', [\App\Http\Controllers\admin\OrderController::class, 'index']);
        Route::middleware(['web', 'auth'])->get('/_test/customer-session', fn () => response()->json(['user_id' => Auth::id()]));
    }

    private function operator(): User
    {
        $id = DB::table('users')->insertGetId(['type' => 4, 'role_id' => 1]);
        return User::findOrFail($id);
    }

    public function test_counter_staff_start_with_orders_even_when_they_can_open_the_dashboard(): void
    {
        DB::table('manage_roles')->where('id',1)->update(['modules'=>'0,1,10']);
        $this->assertSame('/admin/orders', \App\Support\AdminPermissions::landing($this->operator()));
        DB::table('manage_roles')->where('id',1)->update(['modules'=>'10']);
        $this->assertSame('/admin/item', \App\Support\AdminPermissions::landing($this->operator()));
    }

    public function test_order_only_operator_cannot_open_settings_or_modify_products(): void
    {
        DB::table('manage_roles')->where('id', 1)->update(['modules' => '1']);
        $this->actingAs($this->operator());
        $this->getJson('/admin/settings')->assertForbidden();
        $this->postJson('/admin/item/quick-update', ['id' => 1])->assertForbidden();
    }

    /** @dataProvider signageNavigationUsers */
    public function test_tv_navigation_is_under_promotions_for_owner_and_granted_staff(int $type): void
    {
        config(['signage.enabled' => true]);
        app()->setLocale('hu');
        DB::table('manage_roles')->where('id', 1)->update(['modules' => '35']);
        Schema::create('systemaddons', function (Blueprint $table) {
            $table->id(); $table->string('unique_identifier'); $table->integer('activated'); $table->integer('type');
        });
        $user = $this->operator();
        $user->type = $type;
        $this->actingAs($user);
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . view('admin.theme.sidebarcontent')->render());
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[contains(@href, "/admin/signage")]');
        $this->assertSame(1, $links->length);
        $heading = $xpath->query('preceding::h6[1]', $links->item(0))->item(0);
        $this->assertNotNull($heading, 'TV navigation must follow a section heading.');
        $this->assertSame('Akciók', trim($heading->textContent));
    }

    public static function signageNavigationUsers(): array
    {
        return ['owner' => [1], 'staff with only TV access' => [4]];
    }

    public function test_disabled_operator_is_revoked_even_with_an_existing_login(): void
    {
        $user = $this->operator();
        $this->actingAs($user);
        DB::table('users')->where('id', $user->id)->update(['is_available' => 0]);
        $this->get('/admin/orders')->assertRedirect(url('admin'));
        $this->assertGuest();
    }

    public function test_revoked_role_stops_an_existing_session(): void
    {
        $this->actingAs($this->operator());
        DB::table('manage_roles')->where('id', 1)->update(['is_available' => 0]);
        $this->get('/admin/orders')->assertRedirect(url('admin'));
        $this->assertGuest();
    }

    public function test_legacy_installer_and_update_routes_are_always_closed(): void
    {
        $this->get('/install')->assertNotFound();
        $this->get('/install/environment')->assertNotFound();
        $this->post('/update')->assertNotFound();
    }

    public function test_get_cannot_toggle_ordering(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class);
        $this->app['router']->getRoutes()->match(\Illuminate\Http\Request::create('/admin/change-status?status=2', 'GET'));
    }

    /** @dataProvider signageNavigationUsers */
    public function test_owner_and_granted_staff_can_start_and_follow_production_exports(int $type): void
    {
        config(['signage.enabled'=>true]);
        DB::table('manage_roles')->where('id', 1)->update(['modules'=>'35']);
        $user = $this->operator();
        $user->type = $type;
        DB::table('users')->where('id', $user->id)->update(['type'=>$type]);
        $this->actingAs($user);
        $id = '75075499-3431-4b53-b7e8-a9c7e507789e';
        $state = ['id'=>$id, 'status'=>'pending', 'filename'=>'menu.mp4', 'scene'=>0, 'total_scenes'=>1];
        $service = $this->mock(\App\Services\Signage\SignageExport::class);
        $service->shouldReceive('start')->once()->with(3, $user->id)->andReturn($state);
        $service->shouldReceive('latest')->once()->with(3, $user->id)->andReturn($state);
        $service->shouldReceive('status')->twice()->with($id, $user->id)->andReturn($state);
        $file = tempnam(sys_get_temp_dir(), 'gc-export-download-');
        file_put_contents($file, 'private video');
        $service->shouldReceive('downloadPath')->once()->with($id, $user->id)->andReturn($file);
        try {
            $this->postJson('/admin/signage/playlists/3/mp4')->assertStatus(202)->assertJsonPath('id', $id);
            $this->getJson('/admin/signage/playlists/3/mp4')->assertOk()->assertJsonPath('job.id', $id);
            $this->getJson('/admin/signage/exports/'.$id)->assertOk()->assertJsonPath('status', 'pending');
            $this->get('/admin/signage/exports/'.$id.'/download')->assertOk()->assertHeader('Content-Type', 'video/mp4');
        } finally { unlink($file); }
    }

    public function test_staff_without_tv_permission_cannot_access_any_export_endpoint(): void
    {
        config(['signage.enabled'=>true]);
        $this->actingAs($this->operator());
        $id = '75075499-3431-4b53-b7e8-a9c7e507789e';
        $this->postJson('/admin/signage/playlists/3/mp4')->assertForbidden();
        foreach (['playlists/3/mp4', 'exports/'.$id, 'exports/'.$id.'/download'] as $path) {
            $this->getJson('/admin/signage/'.$path)->assertForbidden();
        }
    }
}
