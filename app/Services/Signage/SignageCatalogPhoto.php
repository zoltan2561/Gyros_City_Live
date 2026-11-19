<?php

namespace App\Services\Signage;

class SignageCatalogPhoto
{
    public const FILENAME = '[a-zA-Z0-9_-][a-zA-Z0-9_.-]*\.(?i:png|jpe?g|webp|gif|avif)';

    public static function previews(array $products): array
    {
        $artwork=json_decode(file_get_contents(resource_path('signage/photo-previews.json')),true);
        $prefix=url('public/signage/reference-assets').'/';
        $result=[];
        foreach(array_unique(array_column($products,'image')) as $url){
            if(!$url)continue;
            $small=str_starts_with($url,$prefix) ? ($artwork[substr($url,strlen($prefix))]??null) : self::preview($url);
            if($small)$result[$url]=$small;
        }
        return $result;
    }

    private static function preview(string $url): ?string
    {
        $prefix=url('signage/catalog-photo').'/';
        if (!str_starts_with($url,$prefix) || !preg_match('~^('.self::FILENAME.')\.bin\?display=1$~D',substr($url,strlen($prefix)),$match)) return null;
        $path=storage_path('app/public/admin-assets/images/item/'.$match[1]);
        if (!is_file($path) || !function_exists('imagecreatefromstring') || filesize($path)>8*1024*1024) return null;
        return \Illuminate\Support\Facades\Cache::remember('signage-preview-'.sha1($path.filemtime($path).filesize($path)),86400,function()use($path){
            $size=@getimagesize($path);
            if (!$size || !$size[0] || !$size[1] || $size[0]*$size[1]>8000000) return null;
            $image=@imagecreatefromstring(file_get_contents($path));
            if (!$image) return null;
            $scale=min(1,140/$size[0],110/$size[1]);
            $w=max(1,(int)round($size[0]*$scale));$h=max(1,(int)round($size[1]*$scale));
            $small=imagecreatetruecolor($w,$h);
            imagealphablending($small,false);imagesavealpha($small,true);
            imagecopyresampled($small,$image,0,0,0,0,$w,$h,$size[0],$size[1]);
            ob_start();imagepng($small);$bytes=ob_get_clean();
            return 'data:image/png;base64,'.base64_encode($bytes);
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
