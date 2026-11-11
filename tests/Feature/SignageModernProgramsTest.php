<?php

namespace Tests\Feature;

use App\Services\Signage\SignageDemoPrograms;
use App\Services\Signage\SignageReferenceAssets;
use App\Services\Signage\SignageSceneDefinition;
use Illuminate\Validation\ValidationException;

class SignageModernProgramsTest extends \Tests\TestCase
{
    public function test_seven_programs_include_three_distinct_modern_versions_with_the_original_food_choices(): void
    {
        $programs=collect(app(SignageDemoPrograms::class)->definitions())->keyBy('id');
        $this->assertSame(['burger','menu','pizza','general','modern-burger','modern-menu','modern-pizza'],$programs->keys()->all());
        foreach(['burger','menu','pizza'] as $kind){
            $source=$programs[$kind]['scenes'];$modern=$programs['modern-'.$kind]['scenes'];
            if($kind==='pizza')$source=[$source[1]];
            $this->assertCount(count($source),$modern);
            foreach($modern as $i=>$scene){
                $this->assertSame('modern-'.$kind,$scene['design']);
                $expected=array_map(fn($b)=>array_intersect_key($b,array_flip(['item_id','addons_id','card_id','group'])),$source[$i]['bindings']);
                $actual=array_map(fn($b)=>array_intersect_key($b,array_flip(['item_id','addons_id','card_id','group'])),$scene['bindings']);
                $this->assertSame($expected,$actual);
                app(SignageSceneDefinition::class)->validate($scene);
            }
        }
        $this->assertSame(8,$programs['modern-burger']['scenes'][0]['slide_seconds']);
        $this->assertSame(10,$programs['modern-pizza']['scenes'][0]['slide_seconds']);
        $this->assertSame('Sajttal',$programs['modern-menu']['scenes'][2]['bindings'][1]['label']);
    }

    public function test_modern_fonts_and_price_free_photos_are_pinned_into_the_offline_package(): void
    {
        foreach(['burger','menu','pizza'] as $kind){
            $scene=collect(app(SignageDemoPrograms::class)->definitions())->firstWhere('id','modern-'.$kind)['scenes'][0]??null;
            $this->assertNotNull($scene);
            $assets=app(SignageReferenceAssets::class);
            $scene=$assets->decorate($scene);
            $this->assertArrayHasKey('display-font',$scene['reference_assets']);
            $this->assertArrayNotHasKey('embers',$scene['reference_assets']);
            $files=$assets->files([$scene]);
            $this->assertCount(count($scene['bindings'])+1,$files);
            foreach($files as $file){$this->assertSame(64,strlen($file['checksum']));$this->assertGreaterThan(0,$file['bytes']);}
        }
    }

    public function test_modern_layout_mismatch_is_rejected_instead_of_rendering_a_broken_scene(): void
    {
        foreach(['modern-burger'=>'grid','modern-menu'=>'slideshow','modern-pizza'=>'grid'] as $design=>$wrongType){
            try{
                app(SignageSceneDefinition::class)->validate(['design'=>$design,'type'=>$wrongType,'theme'=>'dark','duration'=>15,'bindings'=>[['id'=>'a','item_id'=>60]]]);
                $this->fail('Wrong modern layout was accepted');
            }catch(ValidationException $e){$this->assertArrayHasKey('design',$e->errors());}
        }
    }
}
