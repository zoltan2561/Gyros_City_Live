<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;

class SignageDeviceTest extends \Tests\TestCase
{
    public function test_pairing_status_polling_does_not_consume_the_new_pairing_code_limit(): void
    {
        $pair=$this->postJson('/signage/api/v1/pairing/start')->assertCreated()->json();
        for($i=0;$i<6;$i++)$this->withToken($pair['nonce'])->postJson('/signage/api/v1/pairing/status')->assertOk();
        $this->withHeader('Authorization','')->postJson('/signage/api/v1/pairing/start')->assertCreated();
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['signage.enabled'=>true,'app.key'=>'base64:'.base64_encode(str_repeat('s',32)),
            'database.default'=>'signage_device_test','database.connections.signage_device_test'=>[
                'driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'session.driver'=>'array']);
        (require base_path('database/migrations/2026_11_09_000001_create_signage_tables.php'))->up();
        (require base_path('database/migrations/2026_11_09_000002_add_signage_delivery_tracking.php'))->up();
        (require base_path('database/migrations/2026_11_09_000004_add_signage_price_ttl.php'))->up();
        Schema::create('users',function(Blueprint $t){$t->id();$t->integer('type');$t->integer('role_id')->default(1);$t->integer('is_available')->default(1);$t->integer('is_deleted')->default(2);$t->rememberToken();});
        Schema::create('manage_roles',function(Blueprint $t){$t->id();$t->string('name');$t->string('modules');$t->integer('is_available')->default(1);});
        Schema::create('languages',function(Blueprint $t){$t->id();$t->string('name')->default('Hungarian');$t->string('code')->default('hu');$t->string('image')->default('');$t->integer('layout')->default(1);$t->integer('is_available')->default(1);$t->integer('is_deleted')->default(2);$t->integer('is_default')->default(1);});
        DB::table('languages')->insert(['id'=>1]);
        DB::table('manage_roles')->insert(['id'=>1,'name'=>'Pult','modules'=>'1']);
        DB::table('users')->insert([['id'=>1,'type'=>1],['id'=>2,'type'=>4]]);
    }

    public function test_pairing_requires_admin_grant_and_nonce_and_token_is_delivered_once(): void
    {
        $pair=$this->postJson('/signage/api/v1/pairing/start')->assertCreated()->json();
        $screen=DB::table('signage_screens')->insertGetId(['name'=>'Bal TV']);
        $this->actingAs(User::find(2))->postJson('/admin/signage/pair',['code'=>$pair['code'],'screen_id'=>$screen])->assertForbidden();
        DB::table('manage_roles')->update(['modules'=>'35']);
        $this->postJson('/admin/signage/pair',['code'=>$pair['code'],'screen_id'=>$screen])->assertOk();
        $this->withToken(str_repeat('x',64))->postJson('/signage/api/v1/pairing/status')->assertNotFound();
        $token=$this->withToken($pair['nonce'])->postJson('/signage/api/v1/pairing/status')->assertOk()->json('token');
        $this->assertNotEmpty($token); $this->assertNotSame($token,DB::table('signage_screens')->value('token_hash'));
        $this->withToken($pair['nonce'])->postJson('/signage/api/v1/pairing/status')->assertNotFound();
        $this->withToken($token)->getJson('/signage/api/v1/manifest')->assertOk()->assertJson(['schema_version'=>1]);
        $this->app['auth']->logout();
        $this->withToken($token)->postJson('/admin/signage/screens',['name'=>'Attack'])->assertRedirect();
        $this->assertSame(1,DB::table('signage_screens')->count());
    }

    public function test_revocation_and_device_isolation(): void
    {
        DB::table('signage_screens')->insert([
            ['id'=>1,'name'=>'A','token_hash'=>hash('sha256','token-a')],
            ['id'=>2,'name'=>'B','token_hash'=>hash('sha256','token-b')],
        ]);
        $this->withToken('token-a')->postJson('/signage/api/v1/heartbeat',['screen_id'=>2,'player_version'=>'1'])->assertOk();
        $this->assertNotNull(DB::table('signage_screens')->where('id',1)->value('last_seen_at'));
        $this->assertNull(DB::table('signage_screens')->where('id',2)->value('last_seen_at'));
        $this->withToken('token-a')->getJson('/signage/api/v1/media/'.str_repeat('a',64))->assertNotFound();
        $this->actingAs(User::find(1))->postJson('/admin/signage/revoke',['screen_id'=>1])->assertOk();
        $this->withToken('token-a')->getJson('/signage/api/v1/manifest')->assertUnauthorized();
    }

    public function test_media_is_isolated_between_real_screen_assignments_and_supports_ranges(): void
    {
        \Storage::fake('local');$path='signage/derived/test.mp4';\Storage::disk('local')->put($path,'0123456789');
        $hash=hash('sha256','0123456789');
        $media=\App\Models\SignageMedia::create(['name'=>'Test','original_path'=>$path,'path'=>$path,'checksum'=>$hash,'mime'=>'video/mp4','status'=>'ready']);
        $release=\App\Models\SignageRelease::create(['playlist_id'=>1,'actor_id'=>1,'snapshot'=>['media'=>[['id'=>$media->id,'checksum'=>$hash]]]]);
        DB::table('signage_screens')->insert([['id'=>1,'name'=>'A','token_hash'=>hash('sha256','a'),'release_id'=>null],['id'=>2,'name'=>'B','token_hash'=>hash('sha256','b'),'release_id'=>$release->id]]);
        $this->withToken('a')->getJson('/signage/api/v1/media/'.$hash)->assertNotFound();
        $this->withToken('b')->withHeader('Range','bytes=2-5')->getJson('/signage/api/v1/media/'.$hash)->assertStatus(206)->assertHeader('Content-Range','bytes 2-5/10');
    }

    public function test_delivered_release_remains_allowed_before_first_activation_heartbeat(): void
    {
        $a=\App\Models\SignageRelease::create(['playlist_id'=>1,'actor_id'=>1,'snapshot'=>['bindings'=>[],'media'=>[]]]);
        $b=\App\Models\SignageRelease::create(['playlist_id'=>1,'actor_id'=>1,'snapshot'=>['bindings'=>[],'media'=>[]]]);
        DB::table('signage_screens')->insert(['id'=>1,'name'=>'A','release_id'=>$a->id,'token_hash'=>hash('sha256','token-a')]);
        $this->withToken('token-a')->getJson('/signage/api/v1/manifest')->assertOk();
        DB::table('signage_screens')->where('id',1)->update(['release_id'=>$b->id]);
        $response=$this->withToken('token-a')->getJson('/signage/api/v1/manifest')->assertOk()->json();
        $this->assertContains($a->id,array_column($response['releases'],'id'));
        $this->withToken('token-a')->postJson('/signage/api/v1/heartbeat',['active_release_id'=>$a->id])->assertOk();
    }

    public function test_playlist_draft_can_be_reordered_without_mutating_a_published_snapshot(): void
    {
        $one=\App\Models\SignageScene::create(['name'=>'One','content'=>[]]);
        $two=\App\Models\SignageScene::create(['name'=>'Two','content'=>[]]);
        $list=\App\Models\SignagePlaylist::create(['name'=>'Draft','scene_ids'=>[$one->id,$two->id]]);
        $release=\App\Models\SignageRelease::create(['playlist_id'=>$list->id,'actor_id'=>1,'snapshot'=>['original'=>true]]);
        $this->actingAs(User::find(1))->postJson('/admin/signage/playlists',[
            'id'=>$list->id,'name'=>'Updated','scene_ids'=>[$two->id,$one->id],
            'version'=>$list->editVersion(),
        ])->assertOk();
        $this->assertSame([$two->id,$one->id],$list->fresh()->scene_ids);
        $this->assertSame(['original'=>true],$release->fresh()->snapshot);
    }

    public function test_expired_pairing_and_disabled_module_are_closed(): void
    {
        $pair=$this->postJson('/signage/api/v1/pairing/start')->json();
        DB::table('signage_pairings')->update(['expires_at'=>now()->subMinute()]);
        $this->withToken($pair['nonce'])->postJson('/signage/api/v1/pairing/status')->assertNotFound();
        config(['signage.enabled'=>false]);
        $this->getJson('/signage/api/v1/manifest')->assertNotFound();
    }
}
