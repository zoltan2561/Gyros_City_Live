@php $scheduledDate = helper::date_format($orderdata->delivery_date); @endphp
<div class="d-flex">
    <p class="fw-semibold fs-7 mb-2">{{ $orderdata->order_type == 1 ? 'Kiszállítás' : 'Átvétel' }}: </p>&nbsp;
    <p class="fw-400 fs-7 text-muted mb-2">{{ $scheduledDate !== '—' ? $scheduledDate : ($orderdata->order_type == 1 ? 'Mielőbbi kiszállítás' : 'Elkészülés után') }}</p>
</div>
@if ($scheduledDate !== '—' && filled($orderdata->delivery_time))
<div class="d-flex">
    <p class="fw-semibold fs-7 mb-2">Időpont: </p>&nbsp;
    <p class="fw-400 fs-7 text-muted mb-2">{{ $orderdata->delivery_time }}</p>
</div>
@endif
