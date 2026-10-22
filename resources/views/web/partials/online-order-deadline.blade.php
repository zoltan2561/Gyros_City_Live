@if ((int) $hours->always_close === 2)
    <small class="d-block text-muted mt-1">{{ trans('opening.online_order_until') }}: {{ $hours->online_order_until ?? '21:30' }}</small>
@endif
