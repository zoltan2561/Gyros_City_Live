@foreach ($offers as $offer)
<section class="city-gift" data-gift-promotion="{{ $offer['promotion']->id }}">
    <div class="city-eyebrow">Ajándék a rendelésed mellé</div>
    <h2>{{ $offer['promotion']->name }}</h2>
    @if ($offer['earned'] < 1)
        <p class="mb-0">Még {{ max(0, $offer['promotion']->required_qty - $offer['qty']) }} db a megadott ételekből, és jár az ajándék.</p>
    @elseif ($offer['gifts']->isEmpty())
        <p class="mb-0" role="status">Az akcióhoz tartozó ajándékok jelenleg nem elérhetők.</p>
    @else
        <p>{{ $offer['earned'] }} db ajándék jár. Válaszd ki, melyiket kéred!</p>
        <form class="city-gift-form" data-gift-form>
            <input type="hidden" name="promotion_id" value="{{ $offer['promotion']->id }}">
            <input type="hidden" name="buynow" value="{{ $buynow }}">
            @foreach ($offer['choices'] as $slot => $choice)
                <label class="city-gift-choice">{{ $slot + 1 }}. ajándék
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
                                <option value="{{ $gift->id }}" @selected($choice === (int) $gift->id)>{{ $gift->item_name }} · 0 Ft</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="hidden" data-gift-extra name="extra_choices[{{ $slot }}]" value="{{ $offer['extraChoices'][$slot] ?? 0 }}">
                </label>
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
