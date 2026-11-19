<?php

namespace App\Services\Signage;

class SignageCatalogPhoto
{
    public const FILENAME = '[a-zA-Z0-9_-][a-zA-Z0-9_.-]*\.(?i:png|jpe?g|webp|gif|avif)';

    public static function previews(array $products): array
    {
        $prefix=url('public/signage/reference-assets').'/';
        $result=[];
        foreach(array_unique(array_column($products,'image')) as $url){
            if(!$url)continue;
            $name=str_starts_with($url,$prefix)?substr($url,strlen($prefix)):'';
            if(preg_match('/^(pizza|burger|menu)-\d+-[0-9a-f]{64}\.webp$/D',$name,$match)){
                $small=self::displayPhoto(public_path('signage/reference-assets/'.$name),$match[1]==='menu'?720:1200);
            }else{
                $small=self::preview($url);
            }
            if($small)$result[$url]=$small;
        }
        return $result;
    }

    private static function preview(string $url): ?string
    {
        $prefix=url('signage/catalog-photo').'/';
        if (!str_starts_with($url,$prefix) || !preg_match('~^('.self::FILENAME.')\.bin\?display=1$~D',substr($url,strlen($prefix)),$match)) return null;
        return self::displayPhoto(storage_path('app/public/admin-assets/images/item/'.$match[1]),960);
    }

    private static function displayPhoto(string $path,int $maxSize): ?string
    {
        if (!is_file($path) || !function_exists('imagecreatefromstring') || filesize($path)>8*1024*1024) return null;
        return \Illuminate\Support\Facades\Cache::remember('signage-display-'.sha1($path.filemtime($path).filesize($path).$maxSize),604800,function()use($path,$maxSize){
            $size=@getimagesize($path);
            if (!$size || !$size[0] || !$size[1] || $size[0]*$size[1]>8000000) return null;
            $image=@imagecreatefromstring(file_get_contents($path));
            if (!$image) return null;
            $scale=min(1,$maxSize/max($size[0],$size[1]));
            $w=max(1,(int)round($size[0]*$scale));$h=max(1,(int)round($size[1]*$scale));
            $small=imagecreatetruecolor($w,$h);
            imagealphablending($small,false);imagesavealpha($small,true);
            imagecopyresampled($small,$image,0,0,0,0,$w,$h,$size[0],$size[1]);
            $webp=function_exists('imagewebp');
            ob_start();
            $ok=$webp?imagewebp($small,null,85):imagepng($small);
            $bytes=ob_get_clean();
            return $ok&&$bytes?'data:image/'.($webp?'webp':'png').';base64,'.base64_encode($bytes):null;
        });
    }

    public static function downloadUrl(?string $url): ?string
    {
        $prefix = url(trim((string) env('ASSETSPATHURL'), '/').'/admin-assets/images/item').'/';
        if ($url === null || !str_starts_with($url, $prefix)) {
            return $url;
        }

        $filename = substr($url, strlen($prefix));
        if (!preg_match('~^'.self::FILENAME.'$~D', $filename)) {
            return $url;
        }

        return url('signage/catalog-photo/'.$filename.'.bin');
    }
}
