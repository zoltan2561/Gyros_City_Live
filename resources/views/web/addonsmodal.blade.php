@php
    if ($itemdata['is_top_deals'] == 1 && $topdeals != null) {
        $price = (int) $topdeals->offer_type === 1
            ? ($itemdata['price'] > $topdeals->offer_amount ? $itemdata['price'] - $topdeals->offer_amount : $itemdata['price'])
            : $itemdata['price'] * (1 - $topdeals->offer_amount / 100);
    } else {
        $price = $itemdata['price'];
    }
@endphp
<div class="modal-header city-builder-header">
    <img src="{{ helper::image_path($itemdata['image_name'] ?? 'item-placeholder.png') }}" width="64" height="64" alt="">
    <div><p class="city-builder-eyebrow">Ahogy te szereted</p><h2 class="modal-title" id="city-builder-title">{{ $itemdata['item_name'] }}</h2><p class="city-builder-base">Alapár: {{ helper::currency_format($price) }}</p></div>
    <button type="button" class="city-builder-close" data-bs-dismiss="modal" aria-label="Összeállító bezárása">×</button>
</div>
<div class="modal-body city-builder-body">
    @include('web.city.builder', ['builderItem' => $itemdata, 'builderModal' => true, 'builderAvailable' => true])
</div>
