GYROS CITY
{{ $title }}

Kedves{{ $recipientName ? ' '.$recipientName : ' Vendég' }}!
{{ $messageText }}

Rendelési szám: {{ $summary['number'] }}
{{ $summary['type'] }}
@if ($summary['address'])
{{ $summary['address'] }}
@endif
@if ($summary['schedule'])
Kért időpont: {{ $summary['schedule'] }}
@endif

@foreach ($summary['items'] as $item)
{{ $item['qty'] }} × {{ $item['name'] }} — {{ $item['total'] }}
@if ($item['addons'])
Kiegészítők: {{ $item['addons'] }}
@endif
@if ($item['extras'])
Extrák: {{ $item['extras'] }}
@endif
@endforeach
@if ($summary['discount'])
Kedvezmény: −{{ $summary['discount'] }}
@endif
@if ($summary['delivery'])
Kiszállítás: {{ $summary['delivery'] }}
@endif
Végösszeg: {{ $summary['total'] }}
@if ($summary['notes'])
Megjegyzés: {{ $summary['notes'] }}
@endif

Az étterem elérhetőségei: {{ url('/contactus') }}
