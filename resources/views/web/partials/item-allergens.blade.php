@php
    $raw = strip_tags((string) ($getitemdata->item_allergens ?? ''));
    $raw = str_replace([';', ' '], [',', ''], $raw);
    $parts = array_filter(array_map('trim', explode(',', $raw)), fn ($value) => $value !== '');
    $displayAllergens = implode(',', $parts);
@endphp
@if ($displayAllergens !== '')
    <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
        <a href="javascript:void(0)" class="d-inline-flex align-items-center gap-2 text-decoration-none"
            onclick="itemsallergens('{{ $getitemdata->id }}','{{ route('get_item_allergens') }}')" aria-label="Allergének megnyitása">
            <span class="btn btn-sm btn-outline-info p-1 lh-1"><i class="fa-solid fa-info"></i></span>
            <span class="fw-600">{{ trans('labels.allergens') }}:</span>
        </a>
        <span class="text-muted">{{ $displayAllergens }}</span>
        <a href="{{ url('alergens.html') }}" class="ms-2 text-decoration-underline small">{{ trans('labels.allergen_table') }}</a>
    </div>
@endif
