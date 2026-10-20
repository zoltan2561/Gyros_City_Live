@php
    $builderPrefix = ($builderModal ? 'modal-' : 'page-') . $builderItem['id'];
    $builderAvailable = $builderAvailable ?? true;
    $builderGroups = collect($builderItem['addons_group'])->filter(fn ($group) => collect($builderItem['addons'])->where('addongroup_id', $group->id)->isNotEmpty())->sortBy('selection_type');
    $builderConfig = [
        'id' => $builderItem['id'], 'slug' => $builderItem['slug'], 'name' => $builderItem['item_name'],
        'price' => (float) $price, 'type' => $builderItem['item_type'], 'tax' => $builderItem['tax'],
        'image' => $builderItem['image_name'] ?? ($builderModal ? 'item-placeholder.png' : ($builderItem->item_image?->image_name ?? 'item-placeholder.png')),
    ];
@endphp
<div class="city-builder {{ $builderModal ? 'city-builder-modal' : 'city-builder-page' }}"
     data-city-builder data-builder-item="{{ json_encode($builderConfig) }}"
     data-cart-url="{{ url('addtocart') }}" data-cart-view-url="{{ route('cart') }}" data-checkout-url="{{ url('checkout') }}"
     data-login-prompt="{{ helper::checkaddons('customer_login') && helper::appdata()->login_required == 1 && !(Auth::user() && Auth::user()->type == 2) ? 'true' : 'false' }}"
     data-orderable="{{ $builderAvailable ? 'true' : 'false' }}"
     data-currency="{{ helper::appdata()->currency }}">
    <div class="city-builder-options">
        @if (!$builderModal)<h2 class="city-builder-heading">Így kéred?</h2><p class="city-builder-intro">Válassz hozzá feltétet, vagy hagyd ki, amit nem szeretsz.</p>@endif
        @foreach ($builderGroups as $group)
            @php
                $required = (int) $group->selection_type === 1;
                $single = (int) $group->selection_count === 1;
                $minimum = $required ? ($single ? 1 : max(1, (int) $group->min_count)) : 0;
                $maximum = $single ? 1 : max(1, (int) $group->max_count);
                $groupKey = $builderPrefix . '-group-' . $group->id;
            @endphp
            <fieldset class="city-builder-group" data-builder-group data-required="{{ $required ? 'true' : 'false' }}" data-min="{{ $minimum }}" data-max="{{ $maximum }}">
                <legend>{{ $group->name }}</legend>
                <div class="city-builder-group-info">
                    <span class="city-builder-badge" data-group-badge>{{ $required ? 'Kötelező' : 'Opcionális' }}</span>
                    <span>{{ $single ? '1 választható' : ($minimum > 0 ? $minimum . '–' . $maximum . ' választható' : 'Legfeljebb ' . $maximum . ' választható') }}</span>
                    <button type="button" class="city-builder-clear" data-group-clear hidden @disabled(!$builderAvailable)>Törlés</button>
                </div>
                <div class="city-builder-choices">
                    @foreach (collect($builderItem['addons'])->where('addongroup_id', $group->id) as $addon)
                        <label class="city-builder-choice" for="{{ $groupKey . '-' . $addon->id }}">
                            <input id="{{ $groupKey . '-' . $addon->id }}" type="{{ $single ? 'radio' : 'checkbox' }}"
                                   name="{{ $groupKey }}" value="{{ $addon->id }}" data-builder-addon
                                   data-price="{{ $addon->price }}" data-name="{{ $addon->name }}"
                                   aria-describedby="{{ $groupKey }}-feedback" @disabled(!$builderAvailable)>
                            <span class="city-builder-choice-name">{{ ltrim($addon->name, '- ') }}</span>
                            <span class="city-builder-choice-price">{{ (float) $addon->price > 0 ? '+' . helper::currency_format($addon->price) : 'Felár nélkül' }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="city-builder-group-feedback" id="{{ $groupKey }}-feedback" data-group-feedback></p>
            </fieldset>
        @endforeach
        @if (count($builderItem['extras'] ?? []) > 0)
            <fieldset class="city-builder-group">
                <legend>Extrák</legend>
                <div class="city-builder-group-info"><span class="city-builder-badge">Opcionális</span><span>Amit még hozzátennél</span></div>
                <div class="city-builder-choices">
                    @foreach ($builderItem['extras'] as $extra)
                        <label class="city-builder-choice" for="{{ $builderPrefix . '-extra-' . $extra->id }}">
                            <input id="{{ $builderPrefix . '-extra-' . $extra->id }}" type="checkbox" value="{{ $extra->id }}" data-builder-extra
                                   data-price="{{ $extra->price }}" data-name="{{ $extra->name }}" @disabled(!$builderAvailable)>
                            <span class="city-builder-choice-name">{{ $extra->name }}</span>
                            <span class="city-builder-choice-price">{{ (float) $extra->price > 0 ? '+' . helper::currency_format($extra->price) : 'Felár nélkül' }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endif
        @if ($builderGroups->isEmpty() && count($builderItem['extras'] ?? []) === 0)
            <p class="city-builder-intro">Ez az étel az étlapon szereplő összeállításban készül. Állítsd be, hány adagot kérsz.</p>
        @endif
        <details class="city-builder-summary"><summary>A választásaid <span data-selection-count>0</span></summary><p data-selection-summary>Még nem választottál kiegészítőt.</p></details>
    </div>
    <div class="city-builder-actions">
        <div class="city-builder-total-row">
            <div><span class="city-builder-total-label">Összesen</span><strong data-builder-total>{{ helper::currency_format($price) }}</strong><small data-builder-unit>1 adag</small></div>
            <div class="city-builder-quantity" role="group" aria-label="Mennyiség">
                <button type="button" data-builder-qty="-1" aria-label="Egy adaggal kevesebb" disabled>−</button>
                <output data-builder-quantity aria-label="Adagok száma">1</output>
                <button type="button" data-builder-qty="1" aria-label="Egy adaggal több" @disabled(!$builderAvailable)>+</button>
            </div>
        </div>
        <p class="city-builder-progress" data-builder-progress role="status" aria-live="polite"></p>
        <div class="city-builder-buttons">
            <button type="button" class="city-builder-submit" data-builder-submit="0" @disabled(!$builderAvailable)><span data-button-label>{{ $builderAvailable ? 'Kosárba teszem' : 'Nem elérhető' }}</span><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></button>
            <button type="button" class="city-builder-quick" data-builder-submit="1" @disabled(!$builderAvailable)><span data-button-label>Tovább a pénztárhoz</span></button>
        </div>
        <p class="city-builder-error" data-builder-error role="alert" hidden></p>
    </div>
</div>
