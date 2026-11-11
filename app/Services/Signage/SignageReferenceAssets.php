<?php

namespace App\Services\Signage;

use Illuminate\Validation\ValidationException;

/** Price-free reference artwork uses immutable, content-addressed files. */
class SignageReferenceAssets
{
    private string $directory;

    public function __construct(?string $directory=null)
    {
        $this->directory=$directory??public_path('signage/reference-assets');
    }

    private function registry(): array
    {
        $path=$this->directory.'/manifest.json';
        return is_file($path)?(json_decode(file_get_contents($path),true)?:[]):[];
    }

    public function image(string $key, int $itemId, ?string $version=null): ?string
    {
        if (!preg_match('/^(burger|menu|pizza)-'.preg_quote((string)$itemId,'/').'$/D',$key)) return null;
        $name=$version??($this->registry()[$key]??null);
        if (!$name||!preg_match('/^'.preg_quote($key,'/').'-[a-f0-9]{64}\.webp$/D',$name)) return null;
        return is_file($this->directory.'/'.$name)?url('public/signage/reference-assets/'.$name):null;
    }

    public function pin(array $binding): array
    {
        if(!empty($binding['artwork'])){
            $url=$this->image($binding['artwork'],(int)$binding['item_id']);
            if(!$url)throw ValidationException::withMessages(['artwork'=>'Hiányzó vagy másik termékhez tartozó referenciafotó.']);
            $binding['artwork_file']=basename(parse_url($url,PHP_URL_PATH));
        }
        return $binding;
    }

    public function decorate(array $scene): array
    {
        if(!str_starts_with($scene['design']??'','reference-')&&!in_array($scene['design']??'', ['modern-burger','modern-menu','modern-pizza'],true))return $scene;
        $scene['reference_assets']=[];
        foreach($this->files([$scene]) as $file){
            if(!preg_match('/^(burger|menu|pizza)-[0-9]+$/',$file['reference_key']))$scene['reference_assets'][$file['reference_key']]=$file;
        }
        return $scene;
    }

    public function files(array $scenes): array
    {
        $requested=[];$registry=$this->registry();
        foreach($scenes as $scene){
            $keys=[];
            if(in_array($scene['design']??'', ['modern-burger','modern-menu','modern-pizza'],true))$keys=['display-font'];
            if(str_starts_with($scene['design']??'','reference-')){
                $keys=['embers','smoke','display-font','script-font','grunge-mask'];
                if(($scene['design']??'')==='reference-intro')$keys[]='promo-dessert';
                if(($scene['design']??'')==='reference-kebab')$keys[]='wood';
            }
            foreach($keys as $key){
                $name=isset($scene['reference_assets'][$key]['url'])?basename(parse_url($scene['reference_assets'][$key]['url'],PHP_URL_PATH)):($registry[$key]??'');
                $requested[$name]=[$key,$name];
            }
            foreach($scene['bindings']??[] as $binding){
                if(empty($binding['artwork']))continue;
                $key=$binding['artwork'];$url=$this->image($key,(int)$binding['item_id'],$binding['artwork_file']??null);
                if(!$url)throw ValidationException::withMessages(['artwork'=>'Hiányzó vagy másik termékhez tartozó referenciafotó.']);
                $name=basename(parse_url($url,PHP_URL_PATH));$requested[$name]=[$key,$name];
            }
        }
        return array_map(function($entry){
            [$key,$name]=$entry;
            if(!preg_match('/^[a-zA-Z0-9_-]+-[a-f0-9]{64}\.(webp|ttf|svg)$/D',$name))throw ValidationException::withMessages(['artwork'=>'Ismeretlen referenciaelem: '.$key]);
            $path=$this->directory.'/'.$name;
            if(!is_file($path))throw ValidationException::withMessages(['artwork'=>'Hiányzó referenciaelem: '.$key]);
            return ['reference_key'=>$key,'url'=>url('public/signage/reference-assets/'.$name),'checksum'=>hash_file('sha256',$path),'bytes'=>filesize($path),
                'mime'=>str_ends_with($name,'.webp')?'image/webp':(str_ends_with($name,'.ttf')?'font/ttf':'image/svg+xml')];
        },array_values($requested));
    }
}
