<?php

namespace Tests\Feature;

use App\Services\Signage\SignageBackgrounds;
use App\Services\Signage\SignageDemoPrograms;
use App\Services\Signage\SignageSceneDefinition;
use Illuminate\Validation\ValidationException;

class SignageBackgroundTest extends \Tests\TestCase
{
    public function test_library_keeps_the_originals_and_the_editable_general_program(): void
    {
        $programs=app(SignageDemoPrograms::class)->definitions();
        $this->assertSame(['burger','menu','pizza','general','modern-burger','modern-menu','modern-pizza'],array_column($programs,'id'));
        $scene=$programs[3]['scenes'][0];
        $this->assertSame('standard',$scene['design']);
        $this->assertSame('slideshow',$scene['type']);
        $this->assertSame('graphite',$scene['backdrop']);
        $this->assertSame(42,app(SignageSceneDefinition::class)->validate($scene)['duration']);
    }

    public function test_every_background_survives_validation_and_unknown_backgrounds_are_rejected(): void
    {
        $scene=['type'=>'grid','theme'=>'dark','duration'=>10,'bindings'=>[['id'=>'a','item_id'=>60]]];
        $options=app(SignageBackgrounds::class)->options();
        $this->assertCount(6,$options);
        foreach($options as $key=>$option){
            $this->assertSame($key,app(SignageSceneDefinition::class)->validate($scene+['backdrop'=>$key])['backdrop']);
        }
        $this->assertArrayNotHasKey('backdrop',app(SignageSceneDefinition::class)->validate($scene));
        $this->expectException(ValidationException::class);
        app(SignageSceneDefinition::class)->validate($scene+['backdrop'=>'untrusted-url']);
    }

    public function test_video_clones_ignore_standard_background_presets(): void
    {
        $scene=app(SignageDemoPrograms::class)->definitions()[0]['scenes'][0];
        $this->assertArrayNotHasKey('backdrop',app(SignageSceneDefinition::class)->validate($scene+['backdrop'=>'wood']));
    }
}
