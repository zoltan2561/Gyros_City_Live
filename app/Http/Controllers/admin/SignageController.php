<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\SignageAudit;
use App\Models\SignagePairing;
use App\Models\SignageScreen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
class SignageController extends Controller {
    public function index(Request $request) {
        $ready=\Schema::hasTable('signage_screens');
        if(!$ready)return view('admin.signage.index',['ready'=>false]);
        $tab=in_array($request->query('tab'),['media','scenes','playlists','schedules'],true)?$request->query('tab'):'screens';
        $screens=SignageScreen::orderBy('name')->get();
        $media=\App\Models\SignageMedia::where('status','!=','retired')->latest()->paginate(12,['*'],'media_page')->withQueryString();
        $readyMedia=\App\Models\SignageMedia::where('status','ready')->get(['id','name','mime']);
        $scenes=\App\Models\SignageScene::orderBy('name')->get();
        $playlists=\App\Models\SignagePlaylist::orderBy('name')->get();
        $releases=\App\Models\SignageRelease::latest()->limit(100)->get();
        $schedules=\App\Models\SignageSchedule::orderByDesc('priority')->paginate(20,['*'],'schedule_page')->withQueryString();
        $items=\App\Models\Item::visibleOnMenu()->orderBy('item_name')->get(['id','item_name','addons_id','item_status','today_unavailable']);
        $groups=\App\Models\AddonsGroup::where('is_available',1)->where('is_deleted',2)->get();
        $addons=\App\Models\Addons::where('is_available',1)->where('is_deleted',2)->get(['id','name','addongroup_id','price']);
        $extras=\App\Models\Extra::get(['id','name','item_id','price']);
        $editing=$request->filled('edit_scene')?\App\Models\SignageScene::findOrFail($request->integer('edit_scene')):null;
        $editingPlaylist=$request->filled('edit_playlist')?\App\Models\SignagePlaylist::findOrFail($request->integer('edit_playlist')):null;
        return view('admin.signage.index',compact('ready','tab','screens','media','readyMedia','scenes','playlists','releases','schedules','items','groups','addons','extras','editing','editingPlaylist'));
    }

    public function scene(Request $request) {
        $request->validate(['id'=>'nullable|integer|exists:signage_scenes,id','name'=>'required|string|max:120','bindings'=>'nullable|array|max:24',
            'bindings.*.addons'=>'nullable|array','bindings.*.addons.*'=>'integer|min:1','bindings.*.extras'=>'nullable|array','bindings.*.extras.*'=>'integer|min:1',
            'bindings.*.group'=>'nullable|string|max:80']);
        $bindings=[];
        foreach($request->input('bindings',[]) as $i=>$b){
            if(empty($b['item_id']))continue;
            $bindings[]=['id'=>'row-'.$i,'card_id'=>!empty($b['group'])?'group-'.substr(hash('sha256',$b['group']),0,20):'row-'.$i,
                'item_id'=>$b['item_id'],'label'=>$b['label']??'','title'=>$b['title']??'',
                'photo_media_id'=>$b['photo_media_id']??null,
                'group'=>$b['group']??'',
                'addons_id'=>implode('|',$b['addons']??[]),'extras_id'=>implode('|',$b['extras']??[])];
        }
        $content=app(\App\Services\Signage\SignageSceneDefinition::class)->validate($request->only(['type','theme','title','duration','media_id','background_media_id','logo_media_id','background','accent','text_color','font_family','name_size','price_size','gap','media_side','media_fit','media_focus_x','media_focus_y'])+['bindings'=>$bindings,'play_to_end'=>$request->boolean('play_to_end')]);
        $record=$request->filled('id')?\App\Models\SignageScene::findOrFail($request->integer('id')):new \App\Models\SignageScene();
        if($record->exists)abort_unless(hash_equals(hash('sha256',json_encode($record->content)),(string)$request->input('version')),409,'A jelenetet közben módosították. Frissítsd az oldalt.');
        $record->fill(['name'=>$request->input('name'),'content'=>$content])->save();
        return $this->saved($request,'Jelenet vázlata elmentve.');
    }

    public function playlist(Request $request) {
        $data=$request->validate(['id'=>'nullable|integer|exists:signage_playlists,id','name'=>'required|string|max:120','scene_ids'=>'required|array|min:1|max:100','scene_ids.*'=>'integer|exists:signage_scenes,id']);
        $record=$request->filled('id')?\App\Models\SignagePlaylist::findOrFail($data['id']):new \App\Models\SignagePlaylist();
        if($record->exists)abort_unless(hash_equals(hash('sha256',json_encode($record->scene_ids)),(string)$request->input('version')),409,'A listát közben módosították. Frissítsd az oldalt.');
        $record->fill(['name'=>$data['name'],'scene_ids'=>array_map('intval',$data['scene_ids'])])->save();
        return $this->saved($request,'Lejátszási lista vázlata elmentve.');
    }

    public function publish(Request $request) {
        $data=$request->validate(['playlist_id'=>'required|integer|exists:signage_playlists,id','screen_ids'=>'required|array|min:1','screen_ids.*'=>'integer|exists:signage_screens,id']);
        app(\App\Services\Signage\SignagePublisher::class)->publish($data['playlist_id'],$data['screen_ids'],$request->user()->id);
        return $this->saved($request,'Kiadás publikálva a kiválasztott kijelzőkre.');
    }

    public function preview(int $id,Request $request) {
        $snapshot=app(\App\Services\Signage\SignagePublisher::class)->build($id);
        // Preview URLs use the admin session; device URLs remain guarded separately.
        foreach($snapshot['scenes'] as &$scene)foreach(['media','background_media','logo'] as $key)if(isset($scene[$key]))$scene[$key]['url']=url('admin/signage/media/'.$scene[$key]['id']);unset($scene);
        $catalog=app(\App\Services\Signage\SignageCatalog::class)->snapshot($snapshot['bindings'],new \DateTimeImmutable());
        foreach($catalog['products'] as &$product)foreach($snapshot['media'] as $media)if($product['image']===$media['url'])$product['image']=url('admin/signage/media/'.$media['id']);unset($product);
        return view('signage.playlist-preview',compact('snapshot','catalog'));
    }

    public function rollback(Request $request) {
        $data=$request->validate(['screen_id'=>'required|integer|exists:signage_screens,id','release_id'=>'required|integer|exists:signage_releases,id']);
        DB::transaction(function()use($data,$request){
            SignageScreen::whereKey($data['screen_id'])->update(['release_id'=>$data['release_id']]);
            SignageAudit::create(['actor_id'=>$request->user()->id,'action'=>'rollback']+$data);
        });
        return $this->saved($request,'Korábbi megjelenés visszaállítva, az árak továbbra is a katalógusból frissülnek.');
    }

    public function schedule(Request $request) {
        $data=$request->validate(['screen_id'=>'required|integer|exists:signage_screens,id','release_id'=>'required|integer|exists:signage_releases,id',
            'days'=>'required|array|min:1|max:7','days.*'=>'integer|between:1,7','starts_on'=>'required|date_format:Y-m-d',
            'ends_on'=>'required|date_format:Y-m-d|after_or_equal:starts_on','start_time'=>'required|date_format:H:i','end_time'=>'required|date_format:H:i','priority'=>'required|integer|between:1,1000']);
        DB::transaction(function()use($data){
            SignageScreen::whereKey($data['screen_id'])->lockForUpdate()->firstOrFail();
            foreach(\App\Models\SignageSchedule::where('screen_id',$data['screen_id'])->get() as $other)
                if(app(\App\Services\Signage\SignageScheduleResolver::class)->overlaps($data,$other->toArray()))throw \Illuminate\Validation\ValidationException::withMessages(['priority'=>'Azonos prioritású időzítések fedik egymást.']);
            \App\Models\SignageSchedule::create($data);
        });
        return $this->saved($request,'Időzítés elmentve.');
    }

    public function unschedule(int $id,Request $request) {
        \App\Models\SignageSchedule::findOrFail($id)->delete();
        return $this->saved($request,'Időzítés eltávolítva.');
    }
    public function screen(Request $request) {
        $data=$request->validate(['id'=>'nullable|integer|exists:signage_screens,id','name'=>'required|string|max:120','location'=>'nullable|string|max:120','offline_price_seconds'=>'nullable|integer|between:60,86400']);
        $record=$request->filled('id')?SignageScreen::findOrFail($data['id']):new SignageScreen();
        unset($data['id']);$record->fill($data)->save();return $this->saved($request,'Kijelző elmentve.');
    }
    public function pair(Request $request) {
        $data=$request->validate(['code'=>'required|digits:8','screen_id'=>'required|integer|exists:signage_screens,id']);
        DB::transaction(function()use($data,$request){
            $pair=SignagePairing::where('code_hash',hash('sha256',$data['code']))->lockForUpdate()->first();
            abort_unless($pair&&!$pair->screen_id&&$pair->expires_at->isFuture(),422,'A párosítási kód lejárt vagy már felhasználták.');
            $screen=SignageScreen::lockForUpdate()->findOrFail($data['screen_id']);$token=bin2hex(random_bytes(32));
            $screen->token_hash=hash('sha256',$token);$screen->last_seen_at=null;$screen->status=null;$screen->delivered_release_ids=[];$screen->save();
            SignagePairing::where('screen_id',$screen->id)->where('id','!=',$pair->id)->delete();
            $pair->screen_id=$screen->id;$pair->delivery=Crypt::encryptString($token);$pair->save();
            SignageAudit::create(['actor_id'=>$request->user()->id,'action'=>'pair','screen_id'=>$screen->id]);
        });
        return $this->saved($request,'Kijelző párosítva.');
    }
    public function revoke(Request $request) {
        $data=$request->validate(['screen_id'=>'required|integer|exists:signage_screens,id']);
        DB::transaction(function()use($data,$request){
            SignageScreen::whereKey($data['screen_id'])->update(['token_hash'=>null]);
            SignagePairing::where('screen_id',$data['screen_id'])->delete();
            SignageAudit::create(['actor_id'=>$request->user()->id,'action'=>'revoke','screen_id'=>$data['screen_id']]);
        });
        return $this->saved($request,'Kijelző hozzáférése visszavonva.');
    }
    private function saved(Request $request,string $message) {
        $last=basename($request->path());
        $tab=in_array($last,['scene','scenes'])?'scenes':(in_array($last,['playlists','publish','rollback'])?'playlists':(in_array($last,['schedules','remove'])?'schedules':'screens'));
        return $request->expectsJson()?response()->json(['ok'=>true,'message'=>$message]):redirect()->route('admin.signage',['tab'=>$tab])->with('success',$message);
    }
}
