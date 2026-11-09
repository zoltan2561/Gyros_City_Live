<?php
namespace Tests\Feature;
use App\Models\SignageMedia;
use App\Models\User;
use App\Jobs\ProcessSignageMedia;
use App\Services\Signage\SignageMediaProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class SignageMediaTest extends SignageDeviceTest {
    public function test_draft_photo_references_prevent_archiving_media(): void {
        $media=SignageMedia::create(['name'=>'Photo','mime'=>'image/png','original_path'=>'signage/photo.png','status'=>'ready']);
        \App\Models\SignageScene::create(['name'=>'Draft','content'=>['bindings'=>[['photo_media_id'=>$media->id]]]]);
        $this->actingAs(User::find(1))->postJson('/admin/signage/media/'.$media->id.'/archive')->assertUnprocessable();
        $this->assertSame('ready',$media->fresh()->status);
    }
    public function test_a_delayed_job_cannot_reactivate_an_archived_upload(): void {
        $media=SignageMedia::create(['name'=>'Archived','mime'=>'image/png','original_path'=>'signage/unused.png','status'=>'retired']);
        app(SignageMediaProcessor::class)->process($media->id);
        $this->assertSame('retired',$media->fresh()->status);
    }
    public function test_actual_mp4_and_mov_are_normalized_by_ffmpeg(): void {
        Storage::fake('local');Queue::fake();$disk=Storage::disk('local');
        foreach(['mp4','mov'] as $extension){
            $source=$disk->path('source.'.$extension);
            $create=new \Symfony\Component\Process\Process([config('signage.ffmpeg'),'-nostdin','-v','error','-y','-f','lavfi','-i','color=c=red:s=160x90:d=1','-c:v','libx264','-pix_fmt','yuv420p',$source]);
            $create->setTimeout(30);$create->mustRun();
            $this->actingAs(User::find(1))->postJson('/admin/signage/media',['name'=>'Sample '.$extension,'file'=>new UploadedFile($source,'sample.'.$extension,null,null,true)])->assertCreated();
            $media=SignageMedia::latest('id')->firstOrFail();app(SignageMediaProcessor::class)->process($media->id);$media->refresh();
            $this->assertSame('ready',$media->status,$media->error??'');$this->assertSame('video/mp4',$media->mime);
            $this->assertSame(160,$media->width);$this->assertSame(90,$media->height);$this->assertGreaterThan(0,(float)$media->duration);
            $this->assertTrue($disk->exists($media->poster_path));
        }
    }
    public function test_upload_is_private_and_background_processing_produces_a_verified_derivative(): void {
        Storage::fake('local'); Queue::fake();
        $this->actingAs(User::find(1))->postJson('/admin/signage/media',[
            'name'=>'Burger fotó','file'=>UploadedFile::fake()->image('burger.png',320,200),'baked_prices'=>0,
        ])->assertCreated();
        $media=SignageMedia::firstOrFail(); $this->assertSame('queued',$media->status);
        Queue::assertPushed(ProcessSignageMedia::class);
        app(SignageMediaProcessor::class)->process($media->id);$media->refresh();
        $this->assertSame('ready',$media->status);$this->assertSame(320,$media->width);
        $this->assertSame(hash_file('sha256',Storage::disk('local')->path($media->path)),$media->checksum);
        $this->assertStringStartsWith('signage/',$media->path);
    }
    public function test_disguised_file_is_rejected_and_invalid_processing_is_reported(): void {
        Storage::fake('local');Queue::fake();
        Storage::disk('local')->put('fake.mp4','<?php echo 1;');
        $this->actingAs(User::find(1))->postJson('/admin/signage/media',[
            'name'=>'Fake','file'=>new UploadedFile(Storage::disk('local')->path('fake.mp4'),'fake.mp4',null,null,true),
        ])->assertUnprocessable();
        Storage::disk('local')->put('signage/bad.png','invalid');
        $media=SignageMedia::create(['name'=>'Bad','mime'=>'image/png','original_path'=>'signage/bad.png']);
        app(SignageMediaProcessor::class)->process($media->id);
        $this->assertSame('failed',$media->fresh()->status);$this->assertNotEmpty($media->fresh()->error);
    }
}
