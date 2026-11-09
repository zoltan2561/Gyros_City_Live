<?php
namespace App\Services\Signage;
use App\Models\SignageScene;
use App\Models\SignagePlaylist;
use App\Models\SignageRelease;
use App\Models\SignageScreen;
use App\Models\SignageMedia;
use App\Models\SignageAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class SignagePublisher {
    public function build(int $playlistId): array {
        $playlist=SignagePlaylist::findOrFail($playlistId);$scenes=[];$bindings=[];$media=[];
        if(!$playlist->scene_ids)throw ValidationException::withMessages(['playlist'=>'A lejátszási lista üres.']);
        foreach($playlist->scene_ids as $position=>$id){
            $record=SignageScene::find($id);
            if(!$record)throw ValidationException::withMessages(['playlist'=>'Hiányzó jelenet.']);
            $scene=app(SignageSceneDefinition::class)->validate($record->content);
            $prefix='s'.$id.'-'.substr(hash('sha256',json_encode($scene)),0,12).'-';
            $scene['id']=$prefix.$position;
            foreach($scene['bindings'] as &$binding){$binding['id']=$prefix.$binding['id'];$bindings[$binding['id']]=$binding;}unset($binding);
            foreach(['media_id'=>'media','background_media_id'=>'background_media','logo_media_id'=>'logo'] as $field=>$target){
                if(empty($scene[$field]))continue;
                $m=SignageMedia::find($scene[$field]);
                if(!$m||$m->status!=='ready'||!$m->path||!Storage::disk('local')->exists($m->path)||!hash_equals((string)$m->checksum,hash_file('sha256',Storage::disk('local')->path($m->path))))throw ValidationException::withMessages(['media'=>'Hiányzó vagy még feldolgozás alatt álló média.']);
                if($m->baked_prices&&(!$m->price_snapshot||!$m->price_valid_until||!$m->price_valid_until->isFuture()))throw ValidationException::withMessages(['media'=>'A beégetett árú médiához friss árellenőrzés szükséges.']);
                $descriptor=['id'=>$m->id,'checksum'=>$m->checksum,'bytes'=>$m->bytes,'mime'=>$m->mime,
                    'url'=>url('signage/api/v1/media/'.$m->checksum),'baked_prices'=>$m->baked_prices,'duration'=>(float)$m->duration];
                if($target==='logo'&&(!str_starts_with($m->mime,'image/')||$m->baked_prices))throw ValidationException::withMessages(['logo'=>'A logóhoz ármentes képet válassz.']);
                $descriptor['fit']=$scene['media_fit']??'contain';
                $descriptor['focus_x']=$scene['media_focus_x']??50;$descriptor['focus_y']=$scene['media_focus_y']??50;
                if($m->baked_prices){
                    $verified=$m->price_snapshot;
                    foreach($verified['bindings'] as $b)$bindings[$b['id']]=$b;
                    if(!$this->bakedValid($m,new \DateTimeImmutable()))throw ValidationException::withMessages(['media'=>'A videó ellenőrzött árai már megváltoztak.']);
                    $descriptor['price_valid_until']=$m->price_valid_until->toIso8601String();
                }
                $scene[$target]=$descriptor;$media[$m->checksum]=$descriptor;
            }
            foreach($scene['bindings'] as $binding){
                if(empty($binding['photo_media_id']))continue;
                $photo=SignageMedia::where('status','ready')->where('baked_prices',false)->where('mime','like','image/%')->find($binding['photo_media_id']);
                if(!$photo||!$photo->path||!Storage::disk('local')->exists($photo->path)||!hash_equals($photo->checksum,hash_file('sha256',Storage::disk('local')->path($photo->path))))throw ValidationException::withMessages(['photo'=>'Hiányzó vagy még nem kész termékfotó.']);
                $media[$photo->checksum]=['id'=>$photo->id,'checksum'=>$photo->checksum,'bytes'=>$photo->bytes,'mime'=>$photo->mime,'url'=>url('signage/api/v1/media/'.$photo->checksum),'baked_prices'=>false];
            }
            $scenes[]=$scene;
        }
        $quote=app(SignageCatalog::class)->snapshot(array_values($bindings),new \DateTimeImmutable());
        foreach($quote['products'] as $p)if(!$p['available'])throw ValidationException::withMessages(['bindings'=>'Nem elérhető étel vagy hiányos feltétválasztás: '.$p['name']]);
        $files=array_values($media);
        foreach($quote['products'] as $p){
            if(!$p['image'])continue;
            $filename=basename(parse_url($p['image'],PHP_URL_PATH));$path=storage_path('app/public/admin-assets/images/item/'.$filename);
            if(is_file($path))$files[]=['url'=>$p['image'],'checksum'=>hash_file('sha256',$path),'bytes'=>filesize($path),'mime'=>mime_content_type($path)];
        }
        return ['name'=>$playlist->name,'scenes'=>$scenes,'bindings'=>array_values($bindings),'media'=>array_values($media),'files'=>$files];
    }
    public function publish(int $playlistId,array $screenIds,int $actorId): array {
        return DB::transaction(function()use($playlistId,$screenIds,$actorId){
            $screens=SignageScreen::whereIn('id',$screenIds)->lockForUpdate()->get();
            if(!$screenIds||$screens->count()!==count(array_unique($screenIds)))throw ValidationException::withMessages(['screens'=>'Válassz létező célkijelzőt.']);
            $snapshot=$this->build($playlistId);
            $release=SignageRelease::create(['playlist_id'=>$playlistId,'snapshot'=>$snapshot,'actor_id'=>$actorId]);
            foreach($screens as $screen){$screen->update(['release_id'=>$release->id]);SignageAudit::create(['actor_id'=>$actorId,'action'=>'publish','screen_id'=>$screen->id,'release_id'=>$release->id]);}
            return ['id'=>$release->id,'snapshot'=>$snapshot];
        });
    }
    public function bakedValid(SignageMedia $media,\DateTimeImmutable $now): bool {
        if(!$media->price_snapshot||!$media->price_valid_until||$media->price_valid_until->getTimestamp()<=$now->getTimestamp())return false;
        $s=app(SignageCatalog::class)->snapshot($media->price_snapshot['bindings'],$now);
        return !in_array(false,array_column($s['products'],'available'),true)
            &&hash_equals($media->price_snapshot['revision'],self::priceRevision($s['products']));
    }
    public static function priceRevision(array $products): string {
        return hash('sha256',json_encode(array_map(fn($p)=>array_intersect_key($p,array_flip(['id','item_id','name','label','price','reference_price','formatted_price','formatted_reference_price','available'])),$products)));
    }
}
