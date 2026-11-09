<?php
namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessSignageMedia;
use App\Models\SignageMedia;
use App\Models\SignageRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class SignageMediaController extends Controller {
    public function verify(int $id,Request $request) {
        $media=SignageMedia::where('baked_prices',true)->where('status','ready')->findOrFail($id);
        $data=$request->validate(['item_ids'=>'nullable|array|max:50','item_ids.*'=>'integer|min:1',
            'scene_ids'=>'nullable|array|max:20','scene_ids.*'=>'integer|exists:signage_scenes,id',
            'confirmed'=>'accepted','valid_until'=>'required|date|after:now|before:+31 days']);
        $bindings=array_map(fn($item)=>['id'=>'m'.$id.'-item'.$item,'item_id'=>(int)$item],array_unique($data['item_ids']??[]));
        foreach(\App\Models\SignageScene::whereIn('id',$data['scene_ids']??[])->get() as $scene)foreach($scene->content['bindings']??[] as $binding){
            $row=array_intersect_key($binding,array_flip(['item_id','addons_id','extras_id','label']));
            $row['id']='m'.$id.'-s'.$scene->id.'-'.substr(hash('sha256',json_encode($binding)),0,20);$bindings[]=$row;
        }
        if(!$bindings||count($bindings)>100)throw \Illuminate\Validation\ValidationException::withMessages(['item_ids'=>'Válassz 1–100 ellenőrzött terméksort.']);
        $snapshot=app(\App\Services\Signage\SignageCatalog::class)->snapshot($bindings,new \DateTimeImmutable());
        foreach($snapshot['products'] as $p)if(!$p['available'])throw \Illuminate\Validation\ValidationException::withMessages(['item_ids'=>'Hiányos termék-összeállítás vagy nem elérhető étel.']);
        $media->update(['price_snapshot'=>['bindings'=>$bindings,'revision'=>\App\Services\Signage\SignagePublisher::priceRevision($snapshot['products'])],'price_valid_until'=>new \DateTimeImmutable($data['valid_until'],new \DateTimeZone('Europe/Budapest'))]);
        \App\Models\SignageAudit::create(['actor_id'=>$request->user()->id,'action'=>'verify-media-'.$media->id]);
        return redirect()->route('admin.signage',['tab'=>'media'])->with('success','A kiválasztott katalógusárak ellenőrzése rögzítve.');
    }
    public function upload(Request $request) {
        $request->validate(['name'=>'required|string|max:160','baked_prices'=>'nullable|boolean','file'=>'required|file|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime|max:'.config('signage.video_max_kb')]);
        $file=$request->file('file');$mime=$file->getMimeType();
        if(str_starts_with($mime,'image/'))$request->validate(['file'=>'max:'.config('signage.image_max_kb')]);
        $path=$file->store('signage/originals','local');
        $media=SignageMedia::create(['name'=>$request->input('name'),'original_path'=>$path,'mime'=>$mime,'bytes'=>$file->getSize(),'baked_prices'=>$request->boolean('baked_prices'),'status'=>'queued']);
        ProcessSignageMedia::dispatch($media->id);
        return $request->expectsJson()?response()->json(['id'=>$media->id,'status'=>'queued'],201):redirect()->route('admin.signage',['tab'=>'media'])->with('success','Média feltöltve, feldolgozásra vár.');
    }
    public function preview(int $id,Request $request) {
        $media=SignageMedia::where('status','ready')->findOrFail($id);
        $path=$request->boolean('poster')?$media->poster_path:$media->path;
        abort_unless($path&&Storage::disk('local')->exists($path),404);
        return response()->file(Storage::disk('local')->path($path),['X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store']);
    }
    public function retry(int $id) {
        $media=SignageMedia::where('status','failed')->findOrFail($id);
        $media->update(['status'=>'queued','error'=>null]);ProcessSignageMedia::dispatch($id);
        return redirect()->route('admin.signage',['tab'=>'media'])->with('success','Feldolgozás újraindítva.');
    }
    public function destroy(int $id) {
        $media=SignageMedia::findOrFail($id);
        // Releases are immutable; protect every retained release, including rollback versions.
        foreach(SignageRelease::cursor() as $release)foreach($release->snapshot['media']??[] as $row)abort_if(($row['id']??null)===$media->id,422,'A médiát egy publikált kiadás használja.');
        foreach(\App\Models\SignageScene::cursor() as $scene){
            $referenced=false;$content=$scene->content;array_walk_recursive($content,function($value,$key)use($media,&$referenced){if(str_ends_with((string)$key,'media_id')&&(int)$value===$media->id)$referenced=true;});
            abort_if($referenced,422,'A médiát egy jelenet használja.');
        }
        // Soft retirement preserves original and derivative bytes for recovery.
        $media->update(['status'=>'retired']);
        return redirect()->route('admin.signage',['tab'=>'media'])->with('success','Média archiválva.');
    }
}
