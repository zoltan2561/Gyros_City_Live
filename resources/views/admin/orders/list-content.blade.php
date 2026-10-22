<div class="table-responsive" id="table-display">
    @include('admin.orders.orderstable')
</div>
@if ($getorders->isEmpty())<p role="status" class="text-muted my-3">Nincs a keresésnek megfelelő rendelés.</p>@endif
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
    <p class="text-muted mb-0">{{ $getorders->firstItem() ?? 0 }}–{{ $getorders->lastItem() ?? 0 }} / {{ $getorders->total() }} rendelés</p>
    {{ $getorders->onEachSide(1)->links() }}
</div>
