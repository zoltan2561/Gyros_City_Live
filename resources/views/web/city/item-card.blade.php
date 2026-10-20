@php
    $available = $itemdata->is_orderable;
    $price = (float) ($itemdata->item_price ?? $itemdata->price);
    $originalPrice = (float) $itemdata->original_price;
    if ($itemdata->is_top_deals == 1 && !empty($topdeals)) {
        $originalPrice = $price;
        $price = (int) $topdeals->offer_type === 1
            ? ($price > $topdeals->offer_amount ? $price - $topdeals->offer_amount : $price)
            : $price * (1 - $topdeals->offer_amount / 100);
    }
@endphp
<div class="col-xl-4 col-lg-4 col-sm-6 col-12" data-city-item="{{ $itemdata->id }}">
    <article class="city-food-card {{ !$available ? 'city-food-unavailable' : '' }}" aria-label="{{ $itemdata->item_name }}{{ !$available ? ', nem elérhető' : '' }}">
        <a href="{{ url('item-'.$itemdata->slug) }}" class="city-food-image" aria-label="{{ $itemdata->item_name }} részletei"><img src="{{ helper::image_path($itemdata->item_image?->image_name ?? 'item-placeholder.png') }}" alt="{{ $itemdata->item_name }}" loading="lazy" width="480" height="330"></a>
        @if (!$available)<span class="city-unavailable-label">{{ $itemdata->today_unavailable ? 'Ma nem elérhető' : 'Jelenleg nem elérhető' }}</span>@endif
        <div class="city-food-body">
            <p class="city-food-category">{{ $itemdata->category_info?->category_name }}</p>
            <div class="d-flex gap-2 align-items-start justify-content-between"><h3><a href="{{ url('item-'.$itemdata->slug) }}">{{ $itemdata->item_name }}</a></h3>@if ($itemdata->item_allergens)<button class="city-allergen" type="button" aria-label="{{ $itemdata->item_name }} allergének" onclick="itemsallergens('{{ $itemdata->id }}','{{ route('get_item_allergens') }}')">i</button>@endif</div>
            <p class="city-food-description">{{ \Illuminate\Support\Str::limit(strip_tags($itemdata->item_description ?? ''), 110) }}</p>
            <div class="city-food-bottom"><div><strong>{{ helper::currency_format($price) }}</strong>@if ($originalPrice > $price)<del>{{ helper::currency_format($originalPrice) }}</del>@endif</div>
                @if ($available)<button type="button" class="city-add addon_modal_{{ $itemdata->slug }}" onclick="showitem('{{ $itemdata->slug }}','{{ url('/show-item') }}')" aria-label="{{ $itemdata->item_name }} hozzáadása"><span>Hozzáadom</span> <i class="fa-solid fa-plus addon_modal_icon_{{ $itemdata->slug }}" aria-hidden="true"></i><span class="loader d-none addon_modal_loader_{{ $itemdata->slug }}"></span></button>
                @else<button type="button" class="city-add" disabled>Nem elérhető</button>@endif
            </div>
        </div>
    </article>
</div>
