<?php

namespace App\Services\Signage;

class SignageCatalogPhoto
{
    public const FILENAME = '[a-zA-Z0-9_-][a-zA-Z0-9_.-]*\.(?i:png|jpe?g|webp|gif|avif)';

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
