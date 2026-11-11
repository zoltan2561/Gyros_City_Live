<?php

namespace Tests\Feature;

use App\Services\Signage\SignageSceneDefinition;
use Illuminate\Validation\ValidationException;

class SignageSlideshowTest extends \Tests\TestCase
{
    private function scene(int $count = 14): array
    {
        return ['type'=>'slideshow','theme'=>'dark','duration'=>15,'slide_seconds'=>7,
            'bindings'=>array_map(fn ($i)=>['id'=>'pizza-'.$i,'item_id'=>$i],range(1,$count))];
    }

    public function test_slideshow_duration_covers_every_product_before_the_playlist_advances(): void
    {
        $scene=app(SignageSceneDefinition::class)->validate($this->scene());
        $this->assertSame(98,$scene['duration']);
        $this->assertSame(7,$scene['slide_seconds']);
        $grouped=$this->scene(3);$grouped['bindings'][0]['card_id']='same';$grouped['bindings'][1]['card_id']='same';
        $this->assertSame(14,app(SignageSceneDefinition::class)->validate($grouped)['duration']);
    }

    public function test_slideshow_defaults_to_seven_seconds_and_supports_twenty_four_cards(): void
    {
        $scene=$this->scene(24);unset($scene['slide_seconds']);
        $this->assertSame(168,app(SignageSceneDefinition::class)->validate($scene)['duration']);
    }

    public function test_slideshow_rejects_readably_short_or_excessive_slide_intervals(): void
    {
        foreach ([4,21] as $seconds) {
            try {app(SignageSceneDefinition::class)->validate(array_replace($this->scene(),['slide_seconds'=>$seconds]));$this->fail('Invalid interval accepted');}
            catch (ValidationException $e) {$this->assertArrayHasKey('slide_seconds',$e->errors());}
        }
    }

    public function test_grid_still_rejects_more_than_eight_cards(): void
    {
        $this->expectException(ValidationException::class);
        app(SignageSceneDefinition::class)->validate(array_replace($this->scene(9),['type'=>'grid']));
    }

    public function test_reference_artwork_cannot_be_assigned_to_a_different_product(): void
    {
        $scene=$this->scene(1);$scene['bindings'][0]['artwork']='pizza-336';
        $this->expectException(ValidationException::class);
        app(SignageSceneDefinition::class)->validate($scene);
    }

    public function test_reference_design_and_artwork_are_retained_and_packaged_for_offline_playback(): void
    {
        $scene=$this->scene(1);$scene['design']='reference-pizza';
        $scene['bindings']=[['id'=>'pizza','item_id'=>336,'artwork'=>'pizza-336']];
        $data=app(SignageSceneDefinition::class)->validate($scene);
        $files=app(\App\Services\Signage\SignageReferenceAssets::class)->files([$data]);
        $this->assertSame('reference-pizza',$data['design']);
        $this->assertMatchesRegularExpression('/\/pizza-336-[a-f0-9]{64}\.webp$/',parse_url(end($files)['url'],PHP_URL_PATH));
        foreach($files as $file){$this->assertSame(64,strlen($file['checksum']));$this->assertGreaterThan(0,$file['bytes']);}
    }

    public function test_reference_definition_binding_ids_do_not_mix_white_and_dark_artwork(): void
    {
        $definitions=app(\App\Services\Signage\SignageDemoPrograms::class)->definitions();$ids=[];
        foreach($definitions as $program)foreach($program['scenes'] as $scene){
            app(SignageSceneDefinition::class)->validate($scene);
            $ids=array_merge($ids,array_column($scene['bindings'],'id'));
        }
        $this->assertCount(count($ids),array_unique($ids));
    }

    public function test_admin_reference_design_selects_its_product_artwork_and_rejects_wrong_layout(): void
    {
        $scene=$this->scene(1);$scene['design']='reference-pizza';$scene['bindings'][0]['item_id']=336;
        $this->assertSame('pizza-336',app(SignageSceneDefinition::class)->validate($scene)['bindings'][0]['artwork']);
        $scene['type']='grid';$this->expectException(ValidationException::class);app(SignageSceneDefinition::class)->validate($scene);
    }

    public function test_old_and_new_artwork_versions_keep_separate_files_and_scene_assets(): void
    {
        \Storage::fake('local');$disk=\Storage::disk('local');$disk->makeDirectory('reference-fixture');$registry=[];
        $write=function($key,$value,$ext='webp')use($disk,&$registry){
            $name=$key.'-'.hash('sha256',$value).'.'.$ext;$disk->put('reference-fixture/'.$name,$value);$registry[$key]=$name;
        };
        foreach(['embers','smoke','display-font','script-font','grunge-mask','pizza-336'] as $key)$write($key,'old-'.$key);
        $disk->put('reference-fixture/manifest.json',json_encode($registry));
        $assets=new \App\Services\Signage\SignageReferenceAssets($disk->path('reference-fixture'));
        $binding=['id'=>'pizza','item_id'=>336,'artwork'=>'pizza-336'];
        $old=$assets->decorate(['design'=>'reference-pizza','bindings'=>[$assets->pin($binding)]]);
        $write('embers','new-background');$write('pizza-336','new-photo');$disk->put('reference-fixture/manifest.json',json_encode($registry));
        $new=$assets->decorate(['design'=>'reference-pizza','bindings'=>[$assets->pin($binding)]]);
        $files=$assets->files([$old,$new]);
        $this->assertCount(8,$files);$this->assertNotSame($old['reference_assets']['embers']['url'],$new['reference_assets']['embers']['url']);
        $this->assertNotSame($old['bindings'][0]['artwork_file'],$new['bindings'][0]['artwork_file']);
        $this->assertSame('old-pizza-336',file_get_contents($disk->path('reference-fixture/'.$old['bindings'][0]['artwork_file'])));
        $this->assertNotNull($assets->image('pizza-336',336,$old['bindings'][0]['artwork_file']));
    }

    public function test_reviewed_price_free_photos_are_pinned_and_packaged_by_the_runtime_registry(): void
    {
        $reviewed=json_decode(file_get_contents(public_path('signage/reference-assets/reviewed-artwork.json')),true);
        $this->assertNotEmpty($reviewed);
        $assets=app(\App\Services\Signage\SignageReferenceAssets::class);
        foreach($reviewed as $key=>$filename){
            $binding=$assets->pin(['id'=>'reviewed-'.$key,'item_id'=>(int)substr($key,strrpos($key,'-')+1),'artwork'=>$key]);
            $this->assertSame($filename,$binding['artwork_file']);
            $file=$assets->files([['bindings'=>[$binding]]])[0];
            $this->assertSame($key,$file['reference_key']);
            $this->assertSame(hash_file('sha256',public_path('signage/reference-assets/'.$filename)),$file['checksum']);
            $this->assertSame('image/webp',$file['mime']);
            $this->assertGreaterThan(0,$file['bytes']);
        }
    }
}
