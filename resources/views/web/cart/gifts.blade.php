@foreach ($offers as $offer)
<section class="city-gift" data-gift-promotion="{{ $offer['promotion']->id }}">
    <div class="city-eyebrow">Ajándék a rendelésed mellé</div>
    <h2>{{ $offer['promotion']->name }}</h2>
    @if ($offer['earned'] < 1)
        <p class="mb-0">Még {{ max(0, $offer['promotion']->required_qty - $offer['qty']) }} db a megadott ételekből, @if ($offer['promotion']->per_item)és onnantól minden darabhoz egy ajándék jár. Mindegyik ízét külön kiválaszthatod.@else és jár az ajándék.@endif</p>
    @elseif ($offer['gifts']->isEmpty())
        <p class="mb-0" role="status">Az akcióhoz tartozó ajándékok jelenleg nem elérhetők.</p>
    @else
        <p>@if ($offer['promotion']->per_item){{ $offer['earned'] }} ajándék jár, minden rendelt darabhoz egy. Mindegyik ízét külön válaszd ki.@else{{ $offer['earned'] }} db ajándék jár. Válaszd ki, melyiket kéred!@endif</p>
        <form class="city-gift-form" data-gift-form>
            <input type="hidden" name="promotion_id" value="{{ $offer['promotion']->id }}">
            <input type="hidden" name="buynow" value="{{ $buynow }}">
            @foreach ($offer['choices'] as $slot => $choice)
                @php($displayChoice = $choice ?? ($offer['gifts']->count() === 1 && $offer['gifts']->first()->extras->isEmpty() && $offer['gifts']->first()->giftGroups->isNotEmpty() ? (int) $offer['gifts']->first()->id : null))
                <div class="city-gift-choice" data-gift-slot>
                    <label>{{ $slot + 1 }}. {{ $offer['promotion']->per_item ? 'ajándék íze' : 'ajándék' }}
                    <select name="choices[]" data-gift-choice required class="form-select">
                        <option value="">Válassz ételt vagy ízt</option>
                        @foreach ($offer['gifts'] as $gift)
                            @if ($gift->extras->isNotEmpty())
                                <optgroup label="{{ $gift->item_name }}">
                                    @foreach ($gift->extras as $extra)
                                        <option value="{{ $gift->id }}" data-extra-id="{{ $extra->id }}" @selected($choice === (int) $gift->id && $offer['extraChoices'][$slot] === (int) $extra->id)>{{ $gift->item_name }} · {{ $extra->name }} · 0 Ft</option>
                                    @endforeach
                                </optgroup>
                            @else
                                <option value="{{ $gift->id }}" @selected($displayChoice === (int) $gift->id)>{{ $gift->item_name }} · 0 Ft</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" data-gift-extra name="extra_choices[{{ $slot }}]" value="{{ $offer['extraChoices'][$slot] ?? 0 }}">
                    </label>
                    @foreach ($offer['gifts'] as $gift)
                        <div data-gift-options="{{ $gift->id }}" @if($displayChoice !== (int) $gift->id) hidden @endif>
                            @foreach ($gift->giftGroups as $group)
                                <fieldset class="mt-3">
                                    <legend class="fs-6">{{ $group->name }}</legend>
                                    @if ((int) $group->selection_count === 1)
                                        <select name="addon_choices[{{ $slot }}][]" class="form-select" data-gift-addon
                                            @if((int) $group->selection_type === 1) data-gift-required @endif
                                            @disabled($displayChoice !== (int) $gift->id) @required($displayChoice === (int) $gift->id && (int) $group->selection_type === 1)>
                                            <option value="">Válassz{{ (int) $group->selection_type === 1 ? ' ízt vagy kiegészítőt' : ' (opcionális)' }}</option>
                                            @foreach ($group->options as $addon)
                                                <option value="{{ $addon->id }}" @selected($choice === (int) $gift->id && in_array((int) $addon->id, $offer['addonChoices'][$slot], true))>{{ $addon->name }} · 0 Ft</option>
                                            @endforeach
                                        </select>
                                    @else
                                        @foreach ($group->options as $addon)
                                            <label class="d-block"><input type="checkbox" data-gift-addon name="addon_choices[{{ $slot }}][]" value="{{ $addon->id }}"
                                                @disabled($displayChoice !== (int) $gift->id) @checked($choice === (int) $gift->id && in_array((int) $addon->id, $offer['addonChoices'][$slot], true))> {{ $addon->name }} · 0 Ft</label>
                                        @endforeach
                                    @endif
                                </fieldset>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="d-flex flex-wrap gap-3 align-items-center mt-3">
                <button type="submit" class="btn city-button">Ajándék kiválasztása</button>
                @if (!$offer['pending'])<span class="city-gift-saved" role="status">✓ Kiválasztva, 0 Ft-ért a rendelésedhez adjuk.</span>@endif
            </div>
            <p class="city-gift-error mb-0 mt-2" role="alert"></p>
        </form>
    @endif
</section>
@endforeach
