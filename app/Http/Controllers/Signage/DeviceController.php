<?php
namespace App\Http\Controllers\Signage;
use App\Http\Controllers\Controller;
use App\Models\SignageMedia;
use App\Models\SignagePairing;
use App\Services\Signage\SignageCatalog;
use App\Services\Signage\SignageManifest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
class DeviceController extends Controller {
    public function start() {
        $nonce=bin2hex(random_bytes(32)); $code=(string)random_int(10000000,99999999);
        SignagePairing::create(['nonce_hash'=>hash('sha256',$nonce),'code_hash'=>hash('sha256',$code),'expires_at'=>now()->addMinutes(10)]);
        return response()->json(['code'=>$code,'nonce'=>$nonce,'expires_in'=>600],201)->header('Cache-Control','no-store');
    }
    public function pairingStatus(Request $request) {
        return DB::transaction(function()use($request){
            $nonce=$request->bearerToken(); abort_unless($nonce&&strlen($nonce)===64,404);
            $pair=SignagePairing::where('nonce_hash',hash('sha256',$nonce))->lockForUpdate()->first();
            abort_unless($pair&&$pair->expires_at->isFuture(),404);
            if(!$pair->delivery)return response()->json(['status'=>'waiting'])->header('Cache-Control','no-store');
            $token=Crypt::decryptString($pair->delivery);$pair->delete();
            return response()->json(['status'=>'paired','token'=>$token])->header('Cache-Control','no-store');
        });
    }
    public function manifest(Request $request) {
        $manifest=DB::transaction(function()use($request){
            $screen=\App\Models\SignageScreen::whereKey($request->attributes->get('signage_screen')->id)->lockForUpdate()->firstOrFail();
            $manifest=app(SignageManifest::class)->forScreen($screen->id,new \DateTimeImmutable());
            $screen->update(['delivered_release_ids'=>array_values(array_unique(array_merge($screen->delivered_release_ids??[],array_column($manifest['releases'],'id'))))]);
            return $manifest;
        });
        return response()->json($manifest)->header('Cache-Control','no-store');
    }
    public function catalog(Request $request) {
        $service=app(SignageManifest::class);$now=new \DateTimeImmutable();
        $manifest=$service->forScreen($request->attributes->get('signage_screen')->id,$now);
        $snapshot=app(SignageCatalog::class)->snapshot($service->bindings($manifest),$now,$request->attributes->get('signage_screen')->offline_price_seconds);
        $snapshot['invalid_media']=[];
        foreach($service->media($manifest) as $m){
            if(empty($m['baked_prices']))continue;
            $record=SignageMedia::find($m['id']);
            $valid=$record&&app(\App\Services\Signage\SignagePublisher::class)->bakedValid($record,$now);
            if(!$valid)$snapshot['invalid_media'][]=$m['checksum'];
            if($valid&&$record->price_valid_until&&$record->price_valid_until->getTimestamp()<strtotime($snapshot['valid_until']))$snapshot['valid_until']=$record->price_valid_until->toIso8601String();
        }
        return response()->json($snapshot)->header('Cache-Control','no-store');
    }
    public function heartbeat(Request $request) {
        $data=$request->validate(['player_version'=>'nullable|string|max:20','active_release_id'=>'nullable|integer',
            'catalog_revision'=>'nullable|string|max:64','scene_id'=>'nullable|string|max:80','error'=>'nullable|string|max:240',
            'offline'=>'nullable|boolean','storage_bytes'=>'nullable|integer|min:0','pending'=>'nullable|string|max:80']);
        $screen=$request->attributes->get('signage_screen');
        if(isset($data['active_release_id'])){
            $manifest=app(SignageManifest::class)->forScreen($screen->id,new \DateTimeImmutable());
            abort_unless(in_array($data['active_release_id'],array_column($manifest['releases'],'id'),true),422);
            $screen->active_release_id=$data['active_release_id'];
            $delivered=$screen->delivered_release_ids??[];$position=array_search($data['active_release_id'],$delivered,true);
            if($position!==false)$screen->delivered_release_ids=array_slice($delivered,$position);
        }
        $screen->last_seen_at=now();$screen->status=$data;$screen->save();
        return response()->json(['ok'=>true])->header('Cache-Control','no-store');
    }
    public function media(Request $request,string $hash) {
        abort_unless(preg_match('/^[a-f0-9]{64}$/D',$hash),404);
        $manifest=app(SignageManifest::class)->forScreen($request->attributes->get('signage_screen')->id,new \DateTimeImmutable());
        abort_unless(in_array($hash,array_column(app(SignageManifest::class)->media($manifest),'checksum'),true),404);
        $media=SignageMedia::where('checksum',$hash)->where('status','ready')->firstOrFail();
        abort_unless($media->path&&\Storage::disk('local')->exists($media->path),404);
        return response()->file(\Storage::disk('local')->path($media->path),['Content-Type'=>$media->mime,'Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
}
