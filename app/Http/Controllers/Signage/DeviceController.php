<?php
namespace App\Http\Controllers\Signage;
use App\Http\Controllers\Controller;
use App\Models\SignageMedia;
use App\Models\SignagePairing;
use App\Services\Signage\SignageCatalog;
use App\Services\Signage\SignageCatalogPhoto;
use App\Services\Signage\SignageStreamAccess;
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
        $manifest=$this->package($request);
        $response=response()->json($manifest)->header('Cache-Control','private, no-store, no-transform');
        return $this->playbackCookies($response,$request);
    }
    private function package(Request $request): array {
        $manifest=DB::transaction(function()use($request){
            $screen=\App\Models\SignageScreen::whereKey($request->attributes->get('signage_screen')->id)->lockForUpdate()->firstOrFail();
            $manifest=app(SignageManifest::class)->forScreen($screen->id,new \DateTimeImmutable(), assignedOnly: true);
            $screen->update(['delivered_release_ids'=>array_values(array_unique(array_merge($screen->delivered_release_ids??[],array_column($manifest['releases'],'id'))))]);
            return $manifest;
        });
        if ($request->header('X-Signage-Include')==='catalog') {
            $manifest['catalog']=$this->catalogSnapshot($request,$manifest,new \DateTimeImmutable());
        }
        if($request->header('X-Signage-Photos')==='preview' && (string)$request->header('X-Signage-Release')!==(string)$manifest['default_release_id']){
            $manifest['photo_previews']=SignageCatalogPhoto::previews($manifest['catalog']['products']??[]);
        }
        if($request->header('X-Signage-Control')==='frame')$manifest['control_frame']=true;
        return $manifest;
    }
    private function playbackCookies($response, Request $request) {
        if ($request->header('X-Signage-Playback')==='online') {
            $screen=$request->attributes->get('signage_screen');
            $path=parse_url(url('signage/api/v1/media'),PHP_URL_PATH).'/';
            $response->cookie(SignageStreamAccess::COOKIE,SignageStreamAccess::issue($screen),15,$path,null,$request->isSecure(),true,false,'strict');
            if($request->header('X-Signage-Control')==='frame'){
                $path=parse_url(url('signage/api/v1/control'),PHP_URL_PATH);
                $response->cookie(SignageStreamAccess::CONTROL_COOKIE,SignageStreamAccess::issue($screen,'control:'),15,$path,null,$request->isSecure(),true,false,'strict');
            }
        }
        return $response;
    }
    public function catalog(Request $request) {
        $service=app(SignageManifest::class);$now=new \DateTimeImmutable();
        $manifest=$service->forScreen($request->attributes->get('signage_screen')->id,$now);
        return response()->json($this->catalogSnapshot($request,$manifest,$now))->header('Cache-Control','no-store');
    }
    private function catalogSnapshot(Request $request,array $manifest,\DateTimeImmutable $now): array {
        $service=app(SignageManifest::class);
        $snapshot=app(SignageCatalog::class)->snapshot($service->bindings($manifest),$now,$request->attributes->get('signage_screen')->offline_price_seconds);
        $snapshot['invalid_media']=[];
        foreach ($snapshot['products'] as $index => $product) {
            $image = SignageCatalogPhoto::downloadUrl($product['image']);
            $snapshot['products'][$index]['image'] = $image !== $product['image'] && $request->header('X-Signage-Playback')==='online'
                ? $image.'?display=1' : $image;
        }
        foreach($service->media($manifest) as $m){
            if(empty($m['baked_prices']))continue;
            $record=SignageMedia::find($m['id']);
            $valid=$record&&app(\App\Services\Signage\SignagePublisher::class)->bakedValid($record,$now);
            if(!$valid)$snapshot['invalid_media'][]=$m['checksum'];
            if($valid&&$record->price_valid_until&&$record->price_valid_until->getTimestamp()<strtotime($snapshot['valid_until']))$snapshot['valid_until']=$record->price_valid_until->toIso8601String();
        }
        return $snapshot;
    }
    public function catalogPhoto(string $filename) {
        $path = storage_path('app/public/admin-assets/images/item/'.$filename);
        abort_unless(is_file($path), 404);

        // The player verifies the original bytes, then restores the MIME type from its manifest.
        return response()->file($path, [
            'Content-Type' => request()->query('display')==='1' ? mime_content_type($path) : 'application/octet-stream',
            'Cache-Control' => 'private, no-store, no-transform',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
    public function heartbeat(Request $request) {
        $assigned=$this->recordStatus($request);
        return response()->json(['ok'=>true,'assigned_release_id'=>$assigned])->header('Cache-Control','private, no-store, no-transform');
    }
    private function recordStatus(Request $request): ?int {
        $data=$request->validate(['player_version'=>'nullable|string|max:20','active_release_id'=>'nullable|integer',
            'catalog_revision'=>'nullable|string|max:64','scene_id'=>'nullable|string|max:80','error'=>'nullable|string|max:240',
            'offline'=>'nullable|boolean','storage_bytes'=>'nullable|integer|min:0','pending'=>'nullable|string|max:80']);
        $authenticated=$request->attributes->get('signage_screen');
        $assigned=DB::transaction(function()use($authenticated,$data){
            $screen=\App\Models\SignageScreen::whereKey($authenticated->id)->lockForUpdate()->firstOrFail();
            abort_unless($screen->token_hash===$authenticated->token_hash,401);
            if(isset($data['active_release_id'])){
                $active=(int)$data['active_release_id'];
                $delivered=$screen->delivered_release_ids??[];
                // An unknown release is not recorded, but the TV still counts as connected.
                if(in_array($active,array_merge($delivered,[$screen->release_id,$screen->active_release_id]),true)){
                    $position=array_search($active,$delivered,true);
                    if($position!==false)$screen->delivered_release_ids=array_slice($delivered,$position);
                    $screen->active_release_id=$active;
                }
            }
            $screen->last_seen_at=now();$screen->status=$data;$screen->save();
            return $screen->release_id;
        });
        return $assigned;
    }
    public function control(Request $request) {
        $request->validate(['sequence'=>'required|integer|min:1','known'=>'nullable|integer','refresh'=>'required|boolean']);
        $assigned=$this->recordStatus($request);
        $packet=['sequence'=>$request->integer('sequence'),'assigned'=>$assigned];
        $changed=(string)$assigned!==(string)$request->input('known');
        if($changed||$request->boolean('refresh')){
            $request->headers->set('X-Signage-Control','frame');
            $request->headers->set('X-Signage-Playback','online');
            $request->headers->set('X-Signage-Include','catalog');
            $request->headers->set('X-Signage-Photos','preview');
            $request->headers->set('X-Signage-Release',(string)$request->input('known'));
            $packet['manifest']=$this->package($request);
        }
        $nonce=base64_encode(random_bytes(18));
        $json=json_encode($packet,JSON_HEX_TAG|JSON_HEX_AMP|JSON_THROW_ON_ERROR);
        $response=response('<!doctype html><meta charset="utf-8"><script nonce="'.$nonce.'">parent.postMessage('.$json.',location.origin);</script>')
            ->header('Cache-Control','private, no-store, no-transform')->header('Referrer-Policy','no-referrer')
            ->header('Content-Security-Policy',"default-src 'none'; script-src 'nonce-".$nonce."'; frame-ancestors 'self'");
        return $this->playbackCookies($response,$request);
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
