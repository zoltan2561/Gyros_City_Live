@extends('admin.theme.default')
@section('styles')
    <link rel="stylesheet" href="{{ url('public/city/admin-orders.css') }}?v={{ filemtime(public_path('city/admin-orders.css')) }}">
    <style>
        .orders-admin .card, .orders-admin .card-body { min-width: 0; }
        .orders-admin #orders-search { font-size: 16px; }
        .orders-admin form[role="search"] .btn { min-height: 44px; }
        @media (max-width: 575px) {
            .page-topbar .navbar-header .header-item { width: 44px; padding-left: 6px; padding-right: 6px; }
            .orders-admin .card-body { padding: 20px 16px; }
        }
    </style>
@endsection
@section('content')
    @include('admin.breadcrumb')
    <div class="container-fluid orders-admin">
        @include('admin.orders.statistics')
        <div class="row">
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body">
                        <form action="{{ url('admin/orders') }}" method="get" class="d-flex flex-wrap gap-2 mb-3" role="search">
                            @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                            <div class="flex-grow-1">
                                <label for="orders-search" class="form-label">Keresés a teljes rendeléslistában</label>
                                <input type="search" id="orders-search" name="q" value="{{ request('q') }}" maxlength="100" class="form-control" placeholder="Rendelésszám, név vagy telefonszám">
                            </div>
                            <div class="d-flex flex-wrap align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">Keresés</button>
                                @if (request('q'))<a href="{{ url('admin/orders') . (request('status') ? '?status=' . urlencode(request('status')) : '') }}" class="btn btn-outline-secondary">Törlés</a>@endif
                                <a href="{{ url('admin/report') }}" class="btn btn-outline-secondary">Kimutatás / export</a>
                            </div>
                        </form>
                        <div class="table-responsive" id="table-display">
                            @include('admin.orders.orderstable')
                        </div>
                        @if ($getorders->isEmpty())<p role="status" class="text-muted my-3">Nincs a keresésnek megfelelő rendelés.</p>@endif
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                            <p class="text-muted mb-0">{{ $getorders->firstItem() ?? 0 }}–{{ $getorders->lastItem() ?? 0 }} / {{ $getorders->total() }} rendelés</p>
                            {{ $getorders->onEachSide(1)->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">{{ trans('labels.payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action=" {{ URL::to('admin/orders/payment_status-' . '2') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div>
                            <input type="hidden" id="booking_number" name="booking_number" value="">
                            <label for="modal_total_amount" class="form-label">
                                {{ trans('labels.total') }} {{ trans('labels.amount') }}
                            </label>
                            <input type="text" class="form-control numbers_only" name="modal_total_amount"
                                id="modal_total_amount" disabled value="">

                            <label for="modal_amount" class="form-label mt-2">
                                {{ trans('labels.cash_received') }}
                            </label>
                            <input type="text" class="form-control numbers_only" name="modal_amount" id="modal_amount"
                                value="" onkeyup="validation($(this).val())">
                            <label for="modal_amount" class="form-label mt-2">
                                {{ trans('labels.change_amount') }}
                            </label>
                            <input type="number" class="form-control" name="ramin_amount" id="ramin_amount" value=""
                                readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-secondary">{{ trans('labels.submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ url('public/city/admin-orders.js') }}?v={{ filemtime(public_path('city/admin-orders.js')) }}"></script>
    <script>
        function codpayment(booking_number, grand_total) {
            $('#modal_total_amount').val(grand_total);
            $('#booking_number').val(booking_number);
            $('#paymentModal').modal('show');
        }

        function validation(value) {
            var remaining = $('#modal_total_amount').val() - value;
            $('#ramin_amount').val(remaining.toFixed(2));
        }
    </script>
    <script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/custom/orders.js') }}"></script>
@endsection
