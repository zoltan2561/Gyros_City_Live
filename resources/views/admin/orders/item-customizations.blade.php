@php
    // Read the saved names, including old orders whose catalog choices no longer exist.
    $choices = [];
    foreach (['addons' => 'choice', 'extras' => 'extra'] as $prefix => $kind) {
        foreach (explode('|', (string) $orders->{$prefix.'_name'}) as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $removed = str_starts_with($name, '--');
            $choices[] = ['kind' => $removed ? 'removed' : $kind, 'name' => $removed ? trim(ltrim($name, '-')) : $name];
        }
    }
@endphp
@if (count($choices))
    <div class="order-customizations" aria-label="Testreszabás">
        @foreach (['choice' => 'Választás:', 'removed' => '− Nem kéri:', 'extra' => '+ Extra:'] as $kind => $label)
            @php $group = array_filter($choices, fn ($choice) => $choice['kind'] === $kind); @endphp
            @if (count($group))
                <div class="order-customization-group order-customization-group--{{ $kind }}">
                    <span class="order-customization-label">{{ $label }}</span>
                    <ul>
                        @foreach ($group as $choice)
                            <li class="order-customization order-customization--{{ $kind }}">{{ $choice['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endforeach
    </div>
@endif
