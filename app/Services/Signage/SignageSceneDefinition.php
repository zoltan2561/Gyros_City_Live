<?php
namespace App\Services\Signage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class SignageSceneDefinition {
    public function validate(array $content): array {
        $v=Validator::make($content,[
            'type'=>'required|in:grid,featured,media,split','theme'=>'required|in:dark,light','title'=>'nullable|string|max:80',
            'duration'=>'required|integer|min:5|max:600','play_to_end'=>'nullable|boolean',
            'background'=>'nullable|regex:/^#[a-fA-F0-9]{6}$/','accent'=>'nullable|regex:/^#[a-fA-F0-9]{6}$/',
            'text_color'=>'nullable|regex:/^#[a-fA-F0-9]{6}$/','font_family'=>'nullable|in:Arial,Trebuchet MS,Georgia',
            'name_size'=>'nullable|integer|between:32,64','price_size'=>'nullable|integer|between:44,96','gap'=>'nullable|integer|between:1,5',
            'media_side'=>'nullable|in:left,right','media_fit'=>'nullable|in:contain,cover',
            'media_focus_x'=>'nullable|integer|between:0,100','media_focus_y'=>'nullable|integer|between:0,100',
            'logo_media_id'=>'nullable|integer|min:1',
            'media_id'=>'nullable|integer|min:1','background_media_id'=>'nullable|integer|min:1',
            'bindings'=>'present|array|max:24','bindings.*.id'=>'required|string|regex:/^[a-zA-Z0-9_-]{1,80}$/',
            'bindings.*.item_id'=>'required|integer|min:1','bindings.*.card_id'=>'nullable|string|regex:/^[a-zA-Z0-9_-]{1,80}$/',
            'bindings.*.photo_media_id'=>'nullable|integer|min:1',
            'bindings.*.group'=>'nullable|string|max:80',
            'bindings.*.label'=>'nullable|string|max:60','bindings.*.title'=>'nullable|string|max:120',
            'bindings.*.addons_id'=>'nullable|string|regex:/^[0-9| ]+$/','bindings.*.extras_id'=>'nullable|string|regex:/^[0-9| ]+$/',
        ]);
        $data=$v->validate();$bindings=$data['bindings'];$ids=array_column($bindings,'id');
        if(count($ids)!==count(array_unique($ids)))throw ValidationException::withMessages(['bindings'=>'A terméksorok azonosítói nem ismétlődhetnek.']);
        $cards=array_unique(array_map(fn($b)=>$b['card_id']??$b['id'],$bindings));
        $max=$data['type']==='featured'?1:($data['type']==='split'?3:8);
        if($data['type']!=='media'&&(!count($cards)||count($cards)>$max))throw ValidationException::withMessages(['bindings'=>'A jelenettípushoz nem megfelelő a termékkártyák száma.']);
        if(in_array($data['type'],['media','split'],true)&&empty($data['media_id']))throw ValidationException::withMessages(['media_id'=>'Válassz kész médiát.']);
        return $data;
    }
}
