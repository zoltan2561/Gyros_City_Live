<?php

namespace App\Services\Signage;

use App\Helpers\helper;
use App\Models\Item;
use App\Models\TopDeals;
use App\Services\CatalogPricing;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Validation\ValidationException;

class SignageCatalog
{
    public function snapshot(array $bindings, DateTimeImmutable $now, ?int $priceTtl = null): array
    {
        $items = Item::visibleOnMenu()->whereIn('id', array_column($bindings, 'item_id'))
            ->with('item_image')->get()->keyBy('id');
        $products = [];
        foreach ($bindings as $binding) {
            $item = $items->get($binding['item_id'] ?? 0);
            $row = ['id' => (string) $binding['id'], 'item_id' => (int) ($binding['item_id'] ?? 0),
                'label' => (string) ($binding['label'] ?? ''), 'name' => $item ? $item->item_name : '',
                'description' => $item ? preg_replace('/\[\[([^|\]]+)\|{1,2}.*?\]\]/su','$1',strip_tags((string)$item->item_description)) : '',
                'available' => false, 'price' => null, 'formatted_price' => null,
                'reference_price' => null, 'formatted_reference_price' => null, 'image' => null,
                'reason' => 'unavailable'];
            if ($item && $item->is_orderable) {
                try {
                    $quote = app(CatalogPricing::class)->selection($item, $binding['addons_id'] ?? null, $binding['extras_id'] ?? null, $now);
                    $price = round($quote['item_price'] + $quote['addons_total_price'] + $quote['extras_total_price'], 2);
                    $reference = max((float) $item->price, (float) $item->original_price)
                        + $quote['addons_total_price'] + $quote['extras_total_price'];
                    $row['available'] = true; $row['reason'] = null;
                    $row['price'] = number_format($price, 2, '.', '');
                    $row['formatted_price'] = helper::currency_format($price);
                    if ($reference > $price) {
                        $row['reference_price'] = number_format($reference, 2, '.', '');
                        $row['formatted_reference_price'] = helper::currency_format($reference);
                    }
                    $filename = $item->item_image ? basename($item->item_image->image_name) : null;
                    if ($filename && is_file(storage_path('app/public/admin-assets/images/item/'.$filename))) {
                        $row['image'] = url(trim((string) env('ASSETSPATHURL'), '/').'/admin-assets/images/item/'.$filename);
                    }
                    if(!empty($binding['photo_media_id'])){
                        $photo=\App\Models\SignageMedia::where('status','ready')->where('mime','like','image/%')->find($binding['photo_media_id']);
                        $row['image']=$photo?url('signage/api/v1/media/'.$photo->checksum):null;
                    }
                    if(empty($binding['photo_media_id'])&&!empty($binding['artwork']))$row['image']=app(SignageReferenceAssets::class)->image($binding['artwork'],(int)$item->id,$binding['artwork_file']??null);
                } catch (ValidationException $e) {
                    $row['reason'] = 'configuration_required';
                }
            }
            $products[] = $row;
        }
        $ttl = max(60, min(86400, $priceTtl ?? (int) config('signage.offline_price_seconds', 1800)));
        $expiry = $now->modify('+'.$ttl.' seconds');
        // Conservatively stop before any known deal transition, including inclusive endpoints.
        if ($items->contains(fn ($item) => (int) $item->is_top_deals === 1) && helper::checkaddons('top_deals')) {
            $deal = TopDeals::first();
            if ($deal && (int) $deal->top_deals_switch === 1) {
                $zone = new DateTimeZone(helper::appdata()->timezone);
                $dates = (int) $deal->deal_type === 1
                    ? [[$deal->start_date, $deal->start_time], [$deal->end_date, $deal->end_time]] : [];
                if ((int) $deal->deal_type === 2) {
                    $local = $now->setTimezone($zone);
                    foreach ([$local, $local->modify('+1 day')] as $day) {
                        $dates[] = [$day->format('Y-m-d'), $deal->start_time];
                        $dates[] = [$day->format('Y-m-d'), $deal->end_time];
                    }
                }
                foreach ($dates as [$date, $time]) {
                    $boundary = new DateTimeImmutable($date.' '.$time, $zone);
                    if ($boundary > $now && $boundary < $expiry) $expiry = $boundary;
                    if ($boundary == $now) $expiry = min($expiry, $boundary->modify('+1 second'));
                }
            }
        }
        return ['schema_version' => 1, 'price_profile' => 'catalog',
            'server_time' => $now->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            'valid_until' => $expiry->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            'catalog_revision' => hash('sha256', json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'products' => $products];
    }
}
