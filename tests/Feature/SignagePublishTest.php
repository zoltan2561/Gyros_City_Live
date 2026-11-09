<?php
namespace Tests\Feature;
use App\Models\SignageScene;
use App\Models\SignagePlaylist;
use App\Models\SignageScreen;
use App\Models\SignageMedia;
use App\Services\Signage\SignagePublisher;
use App\Services\Signage\SignageManifest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SignagePublishTest extends SignageCatalogTest {
    protected function setUp(): void {
        parent::setUp();
        (require base_path('database/migrations/2026_11_09_000001_create_signage_tables.php'))->up();
    }
    private function playlist(): SignagePlaylist {
        $scene=SignageScene::create(['name'=>'Burger','content'=>['type'=>'grid','theme'=>'dark','title'=>'Burgerek','duration'=>15,'bindings'=>[['id'=>'burger','item_id'=>1]]]]);
        return SignagePlaylist::create(['name'=>'Ebéd','scene_ids'=>[$scene->id]]);
    }
    public function test_publish_is_immutable_targeted_and_catalog_prices_remain_live(): void {
        $list=$this->playlist();$a=SignageScreen::create(['name'=>'A']);$b=SignageScreen::create(['name'=>'B']);
        $release=app(SignagePublisher::class)->publish($list->id,[$a->id],1);
        $this->assertSame($release['id'],$a->fresh()->release_id);$this->assertNull($b->fresh()->release_id);
        SignageScene::first()->update(['content'=>['type'=>'grid','title'=>'Vázlat']]);
        $manifest=app(SignageManifest::class)->forScreen($a->id,new \DateTimeImmutable());
        $this->assertSame('Burgerek',$manifest['releases'][0]['snapshot']['scenes'][0]['title']);
        DB::table('item')->update(['price'=>3000]);
        $bindings=app(SignageManifest::class)->bindings($manifest);
        $snapshot=app(\App\Services\Signage\SignageCatalog::class)->snapshot($bindings,new \DateTimeImmutable());
        $this->assertSame('3000.00',$snapshot['products'][0]['price']);
    }
    public function test_missing_required_choice_and_unprocessed_media_block_publish(): void {
        $list=$this->playlist();$a=SignageScreen::create(['name'=>'A']);
        $media=SignageMedia::create(['name'=>'Pending','mime'=>'video/quicktime','original_path'=>'signage/file.mov']);
        $scene=SignageScene::first();$content=$scene->content;$content['media_id']=$media->id;$content['type']='split';$scene->update(['content'=>$content]);
        $this->expectException(ValidationException::class);
        app(SignagePublisher::class)->publish($list->id,[$a->id],1);
    }

    public function test_expired_baked_price_media_does_not_expire_unrelated_dynamic_prices(): void {
        config(['signage.enabled'=>true]);
        $media=SignageMedia::create(['name'=>'Old ad','mime'=>'video/mp4','original_path'=>'signage/old.mp4','status'=>'ready','baked_prices'=>true,'price_valid_until'=>now()->subMinute(),'price_snapshot'=>['bindings'=>[],'revision'=>'old']]);
        $release=\App\Models\SignageRelease::create(['playlist_id'=>1,'actor_id'=>1,'snapshot'=>['bindings'=>[['id'=>'burger','item_id'=>1]],'media'=>[['id'=>$media->id,'checksum'=>str_repeat('a',64),'baked_prices'=>true]]]]);
        SignageScreen::create(['name'=>'A','token_hash'=>hash('sha256','device'),'release_id'=>$release->id]);
        $result=$this->withToken('device')->getJson('/signage/api/v1/catalog')->assertOk()->json();
        $this->assertContains(str_repeat('a',64),$result['invalid_media']);
        $this->assertGreaterThan(time(),strtotime($result['valid_until']));
        $this->assertTrue($result['products'][0]['available']);
    }

    public function test_final_overnight_schedule_is_retained_after_midnight(): void {
        $screen=SignageScreen::create(['name'=>'A']);
        \App\Models\SignageSchedule::create(['screen_id'=>$screen->id,'release_id'=>1,'days'=>[1],'starts_on'=>'2026-11-09','ends_on'=>'2026-11-09','start_time'=>'22:00','end_time'=>'02:00','priority'=>1]);
        $manifest=app(SignageManifest::class)->forScreen($screen->id,new \DateTimeImmutable('2026-11-10T00:30:00+01:00'));
        $this->assertCount(1,$manifest['schedules']);
    }

    public function test_display_photo_override_is_added_to_the_private_media_manifest(): void {
        \Storage::fake('local');$disk=\Storage::disk('local');$file=\Illuminate\Http\UploadedFile::fake()->image('photo.png',100,100);
        $path='signage/derived/photo.png';$disk->put($path,file_get_contents($file->getRealPath()));
        $media=SignageMedia::create(['name'=>'Alternate photo','original_path'=>$path,'path'=>$path,'checksum'=>hash_file('sha256',$disk->path($path)),'mime'=>'image/png','bytes'=>$disk->size($path),'status'=>'ready']);
        $list=$this->playlist();$scene=SignageScene::first();$content=$scene->content;$content['bindings'][0]['photo_media_id']=$media->id;$scene->update(['content'=>$content]);
        $snapshot=app(SignagePublisher::class)->build($list->id);
        $this->assertContains($media->id,array_column($snapshot['media'],'id'));
        $catalog=app(\App\Services\Signage\SignageCatalog::class)->snapshot($snapshot['bindings'],new \DateTimeImmutable());
        $this->assertStringEndsWith('/signage/api/v1/media/'.$media->checksum,$catalog['products'][0]['image']);
    }
}
