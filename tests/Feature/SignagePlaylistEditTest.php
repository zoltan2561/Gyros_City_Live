<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\SignageController;
use App\Models\SignagePlaylist;
use App\Models\SignageRelease;
use App\Models\SignageScene;
use App\Models\SignageScreen;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SignagePlaylistEditTest extends SignageCatalogTest
{
    protected function setUp(): void
    {
        parent::setUp();
        (require base_path('database/migrations/2026_11_09_000001_create_signage_tables.php'))->up();
    }

    private function scene(string $name): SignageScene
    {
        return SignageScene::create(['name'=>$name,'content'=>[
            'type'=>'grid','theme'=>'dark','title'=>$name,'duration'=>15,
            'bindings'=>[['id'=>'burger','item_id'=>1]],
        ]]);
    }

    private function saveList(SignagePlaylist $list, array $changes = [])
    {
        $request=Request::create('/admin/signage/playlists','POST',array_replace([
            'id'=>$list->id,'name'=>$list->name,'scene_ids'=>$list->scene_ids,
            'version'=>hash('sha256',json_encode([$list->name,$list->scene_ids])),
        ],$changes));
        $request->headers->set('Accept','application/json');
        $request->setUserResolver(fn ()=>(object)['id'=>1]);
        return app(SignageController::class)->playlist($request);
    }

    public function test_background_choice_is_saved_and_retained_in_the_tv_snapshot(): void
    {
        $scene=$this->scene('Általános reklám');
        foreach(app(\App\Services\Signage\SignageBackgrounds::class)->options() as $key=>$preset){
            $request=Request::create('/admin/signage/scenes','POST',[
                'id'=>$scene->id,'name'=>$scene->name,'version'=>hash('sha256',json_encode($scene->content)),
                'type'=>'grid','design'=>'standard','duration'=>15,'backdrop'=>$key,
                'theme'=>$preset['theme'],'background'=>$preset['background'],'accent'=>$preset['accent'],'text_color'=>$preset['text_color'],
                'bindings'=>[['item_id'=>1]],
            ]);
            $request->headers->set('Accept','application/json');
            $this->assertSame(200,app(SignageController::class)->scene($request)->getStatusCode());
            $scene->refresh();$this->assertSame($key,$scene->content['backdrop']);
        }
        $list=SignagePlaylist::create(['name'=>'Általános reklám','scene_ids'=>[$scene->id]]);
        $screen=SignageScreen::create(['name'=>'TV']);
        $this->saveList($list,['screen_ids'=>[$screen->id]]);
        $snapshot=SignageRelease::findOrFail($screen->fresh()->release_id)->snapshot;
        $this->assertSame('solid',$snapshot['scenes'][0]['backdrop']);
        $this->assertSame('#152130',$snapshot['scenes'][0]['background']);
    }

    public function test_one_save_updates_only_selected_tvs_and_keeps_the_old_release_immutable(): void
    {
        $one=$this->scene('One');$two=$this->scene('Two');
        $list=SignagePlaylist::create(['name'=>'Menu','scene_ids'=>[$one->id,$two->id]]);
        $old=SignageRelease::create(['playlist_id'=>$list->id,'actor_id'=>1,'snapshot'=>['original'=>true]]);
        $a=SignageScreen::create(['name'=>'A','release_id'=>$old->id]);
        $b=SignageScreen::create(['name'=>'B','release_id'=>$old->id]);

        $this->assertSame(200,$this->saveList($list,[
            'name'=>'New menu','scene_ids'=>[$two->id,$one->id,$two->id],'screen_ids'=>[$a->id],
        ])->getStatusCode());

        $this->assertNotSame($old->id,$a->fresh()->release_id);
        $this->assertSame($old->id,$b->fresh()->release_id);
        $this->assertSame(['original'=>true],$old->fresh()->snapshot);
        $snapshot=SignageRelease::findOrFail($a->fresh()->release_id)->snapshot;
        $this->assertSame('New menu',$snapshot['name']);
        $this->assertSame(['Two','One','Two'],array_column($snapshot['scenes'],'title'));
    }

    public function test_unpublishable_content_rolls_back_the_list_and_every_tv_assignment(): void
    {
        $one=$this->scene('One');$bad=SignageScene::create(['name'=>'Invalid','content'=>[]]);
        $list=SignagePlaylist::create(['name'=>'Menu','scene_ids'=>[$one->id]]);
        $screen=SignageScreen::create(['name'=>'A']);
        try {
            $this->saveList($list,['name'=>'Changed','scene_ids'=>[$bad->id],'screen_ids'=>[$screen->id]]);
            $this->fail('Invalid content was accepted');
        } catch (ValidationException $e) {
            $this->assertSame('Menu',$list->fresh()->name);
            $this->assertSame([$one->id],$list->fresh()->scene_ids);
            $this->assertNull($screen->fresh()->release_id);
            $this->assertSame(0,SignageRelease::count());
        }
    }

    public function test_a_stale_name_cannot_overwrite_a_newer_edit(): void
    {
        $one=$this->scene('One');$list=SignagePlaylist::create(['name'=>'Menu','scene_ids'=>[$one->id]]);
        $list->update(['name'=>'New name']);
        try {
            $this->saveList($list,['name'=>'Stale edit','version'=>hash('sha256',json_encode(['Menu',[$one->id]]))]);
            $this->fail('A stale edit was accepted');
        } catch (HttpException $e) {
            $this->assertSame(409,$e->getStatusCode());
            $this->assertSame('New name',$list->fresh()->name);
        }
    }

    public function test_saving_without_selected_tvs_keeps_a_draft(): void
    {
        $one=$this->scene('One');$list=SignagePlaylist::create(['name'=>'Menu','scene_ids'=>[$one->id]]);
        $this->assertSame(200,$this->saveList($list,['name'=>'Draft'])->getStatusCode());
        $this->assertSame('Draft',$list->fresh()->name);
        $this->assertSame(0,SignageRelease::count());
    }

    public function test_the_editor_opens_a_list_and_preselects_only_its_current_tvs(): void
    {
        $scene=$this->scene('One');$list=SignagePlaylist::create(['name'=>'Menu','scene_ids'=>[$scene->id]]);
        $release=SignageRelease::create(['playlist_id'=>$list->id,'actor_id'=>1,'snapshot'=>['name'=>'Menu']]);
        $assigned=SignageScreen::create(['name'=>'A','release_id'=>$release->id]);
        SignageScreen::create(['name'=>'B']);
        $data=app(SignageController::class)->index(Request::create('/admin/signage'))->getData();
        $this->assertSame('playlists',$data['tab']);
        $this->assertSame($list->id,$data['editingPlaylist']->id);
        $html=view('admin.signage.playlists',$data)->render();
        $this->assertStringContainsString('value="'.$assigned->id.'" type="checkbox" checked',$html);
        $this->assertSame(1,substr_count($html,'type="checkbox" checked'));

        session()->flashInput(['screen_selection'=>'1','name'=>'Changed']);
        $html=view('admin.signage.playlists',$data)->render();
        $this->assertSame(0,substr_count($html,'type="checkbox" checked'));
    }

    public function test_a_failed_new_list_is_not_left_behind_after_a_failed_tv_update(): void
    {
        $bad=SignageScene::create(['name'=>'Invalid','content'=>[]]);
        $screen=SignageScreen::create(['name'=>'A']);
        $list=new SignagePlaylist(['name'=>'New','scene_ids'=>[$bad->id]]);
        try {
            $this->saveList($list,['screen_ids'=>[$screen->id]]);
            $this->fail('Invalid content was accepted');
        } catch (ValidationException $e) {
            $this->assertSame(0,SignagePlaylist::count());
            $this->assertSame(0,SignageRelease::count());
            $this->assertNull($screen->fresh()->release_id);
        }
    }
}
