@php
    // Read the saved names, including old orders whose catalog choices no longer exist.
    $choices = [];
    foreach (['addons' => 'choice', 'extras' => 'extra'] as $prefix => $kind) {
        foreach (explode('|', (string) $orders->{$prefix.'_name'}) as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $removed = str_starts_with($name, '---');
            $choices[] = ['kind' => $removed ? 'removed' : $kind, 'name' => $removed ? trim(ltrim($name, '-')) : $name];
        }
    }
@endphp
@if (count($choices))
    <ul class="order-customizations" aria-label="Testreszabás">
        @foreach ($choices as $choice)
            <li class="order-customization order-customization--{{ $choice['kind'] }}">
                <span class="order-customization-label">{{ $choice['kind'] === 'removed' ? '− Nem kéri:' : ($choice['kind'] === 'extra' ? '+ Extra:' : 'Választás:') }}</span>
                {{ $choice['name'] }}
            </li>
        @endforeach
    </ul>
@endif
