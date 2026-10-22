@extends('web.layout.default')
@section('page_title')
    | {{ trans('labels.my_cart') }}
@endsection
@section('content')
    <div class="breadcrumb-sec">
        <div class="container">
            <div class="breadcrumb-sec-content">
                <nav class="text-dark breadcrumb-divider" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item {{ session()->get('direction') == '2' ? 'breadcrumb-item-rtl ps-0' : '' }}">
                            <a class="text-dark fw-600" href="{{ route('home') }}">{{ trans('labels.home') }}</a>
                        </li>
                        <li class="breadcrumb-item {{ session()->get('direction') == '2' ? 'breadcrumb-item-rtl ps-0' : '' }} active"
                            aria-current="page">{{ trans('labels.cart') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section>
        <div class="container">
            <div class="cart-view my-5">
                <div id="city-cart-gifts" aria-live="polite"></div>
                @if (count($getcartlist) > 0)
                    <div class="row">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                            <div class="card px-0 overflow-hidden border-bottom-0 rounded-3">
                                <div class="table-responsive">
                                    <table class="table m-0">
                                        <thead class="table-light bg-primary">
                                        <tr>
                                            <th class="cart-table-title p-3 text-white">{{ trans('labels.item') }}</th>
                                            <th class="cart-table-title p-3 text-white">{{ trans('labels.price') }}</th>
                                            <th class="cart-table-title p-3 text-white">{{ trans('labels.qty') }}</th>
                                            <th class="cart-table-title p-3 text-white">{{ trans('labels.total') }}</th>
                                            <th class="cart-table-title p-3 text-white text-center">{{ trans('labels.action') }}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $order_total = 0;
                                            $total_item_qty = 0;
                                        @endphp
                                        @foreach ($getcartlist as $cartitems)
                                            @php $isAvailable = $cartAvailability->get($cartitems->item_id)?->is_orderable ?? false; @endphp
                                            {{-- ⬇⬇⬇ SOR AZONOSÍTÓ A KÖNNYŰ ELTÁVOLÍTÁSHOZ --}}
                                            <tr id="row-{{ $cartitems->id }}" data-cart-row>
                                                <td>
                                                    <div class="tbl_cart_product gap-3">
                                                        <div class="col-auto d-none d-md-flex  justify-content-center item-img-none">
                                                            <div class="item-img">
                                                                <img src="{{ helper::image_path($cartitems->item_image) }}" alt="item-image">
                                                            </div>
                                                        </div>
                                                        <div class="tbl_cart_product_caption">
                                                            <h5 class="tbl_pr_title line-2 mb-1 fs-6">
                                                                <img
                                                                    @if ($cartitems->item_type == 1)
                                                                        src="{{ helper::image_path('veg.svg') }}"
                                                                    @else
                                                                        src="{{ helper::image_path('nonveg.svg') }}"
                                                                    @endif
                                                                    class="item-type-image" alt="">
                                                                {{ $cartitems->item_name }}
                                                                @if (!$isAvailable)<span class="d-block small text-danger mt-2">Jelenleg nem elérhető. Kérjük, vedd ki a kosárból.</span>@endif
                                                            </h5>
                                                            @if ($cartitems->addons_id != '' || $cartitems->extras_id != '')
                                                                <small>
                                                                    <a class="text-muted fw-400 fs-7" href="javascript:void(0)"
                                                                       onclick="showaddons('{{ $cartitems['addons_name'] }}','{{ $cartitems['addons_price'] }}','{{ $cartitems['extras_name'] }}','{{ $cartitems['extras_price'] }}','{{ $cartitems['item_name'] }}')">
                                                                        {{ trans('labels.customize') }}
                                                                    </a>
                                                                </small>
                                                                <br>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                @php
                                                    $total_price =
                                                        ($cartitems->item_price + $cartitems->addons_total_price + $cartitems->extras_total_price) * $cartitems->qty;
                                                    $order_total += (float) $total_price;
                                                    $total_item_qty += $cartitems->qty;
                                                @endphp
                                                <td>
                                                    <h4 class="tbl_org_price">
                                                        {{ helper::currency_format($cartitems->item_price + $cartitems->addons_total_price + $cartitems->extras_total_price) }}
                                                    </h4>
                                                </td>
                                                <td>
                                                    <nav aria-label="Page navigation example">
                                                        <ul class="qtladd mb-0 {{ session()->get('direction') == '2' ? 'rtl' : '' }}">
                                                            <li>
                                                                <button class="qty_button" aria-label="{{ $cartitems->item_name }} mennyiségének csökkentése"
                                                                        onclick="qtyupdate('{{ $cartitems['id'] }}','minus','{{ URL::to('/cart/qtyupdate') }}')">
                                                                    <span aria-hidden="true"><i class="fa-light fa-minus fs-10"></i></span>
                                                                </button>
                                                            </li>
                                                            <li class="qtl-count">
                                                                <input type="text" class="border py-1 w-100"
                                                                       id="number_{{ $cartitems->id }}" name="number"
                                                                       value="{{ $cartitems->qty }}" readonly>
                                                            </li>
                                                            <li>
                                                                <button class="qty_button" aria-label="{{ $cartitems->item_name }} mennyiségének növelése" @disabled(!$isAvailable)
                                                                        onclick="qtyupdate('{{ $cartitems['id'] }}','plus','{{ URL::to('/cart/qtyupdate') }}')">
                                                                    <span aria-hidden="true"><i class="fa-light fa-plus fs-10"></i></span>
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </nav>
                                                </td>
                                                <td>
                                                    <h4 class="tbl_org_price">{{ helper::currency_format($total_price) }}</h4>
                                                </td>
                                                <td>
                                                    <div class="tbl_pr_action">
                                                        {{-- ⬇⬇⬇ TÖRLÉS GOMB (NEM LINK), NINCS NAVIGÁCIÓ --}}
                                                        <button type="button"
                                                                class="tbl_remove btn-remove"
                                                                title="{{ trans('labels.delete') }}" aria-label="{{ $cartitems->item_name }} törlése a kosárból"
                                                                data-id="{{ $cartitems['id'] }}"
                                                                data-row="#row-{{ $cartitems['id'] }}"
                                                                data-url="{{ url('/cart/deleteitem') }}">
                                                            <i class="fa-light fa-trash-can fs-7"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-3 justify-content-between mt-0 align-items-center">
                                <div class="col-xl-3 col-lg-4 col-sm-6 col-12 {{ session()->get('direction') == '2' ? 'text-end' : '' }}">
                                    <a href="{{ URL::to('/') }}" class="btn btn-outline-dark w-100">
                                        <i class="fa-solid {{ session()->get('direction') == '2' ? 'fa-circle-arrow-right ms-2' : 'fa-circle-arrow-left me-2' }}"></i>
                                        {{ trans('labels.continue_shopping') }}
                                    </a>
                                </div>
                                <div class="col-xl-3 col-lg-4 col-sm-6 col-12 {{ session()->get('direction') == '2' ? 'text-start' : 'text-end' }}">
                                    <button class="btn btn-primary w-100 d-flex gap-3 justify-content-center align-items-center cart_checkout"
                                            onclick="isopenclose('{{ URL::to('/isopenclose') }}','{{ $total_item_qty }}','{{ $order_total }}')">
                                        {{ trans('labels.continue') }}
                                        <div class="loader d-none cart_checkout_loader"></div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    @include('web.nodata', ['emptyTitle' => trans('reviews.empty_cart'), 'emptyDescription' => trans('reviews.empty_cart_help'), 'emptyLink' => route('categories'), 'emptyLinkLabel' => trans('reviews.browse_menu')])
                @endif
            </div>
        </div>
    </section>

    {{-- Segéd: CSRF token JS-hez (ha a layoutban nincs meta) --}}
    <input type="hidden" id="csrf_token" value="{{ csrf_token() }}">

    <input type="hidden" name="request_url" id="request_url" value="{{ request()->segments()[0] }}">
    @include('web.subscribeform')
@endsection

@section('scripts')
    <script src="{{ url(env('ASSETSPATHURL') . 'web-assets/js/custom/cart.js') }}"></script>

    {{-- ⬇⬇⬇ AJAX törlés: nem navigál, nem „Cancelled” --}}
    <script>
        (function () {
            function getCsrf() {
                var m = document.querySelector('meta[name="csrf-token"]');
                if (m && m.content) return m.content;
                var h = document.getElementById('csrf_token');
                return h ? h.value : '';
            }

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('.btn-remove');
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();

                var id    = btn.getAttribute('data-id');
                var url   = btn.getAttribute('data-url') || '{{ url('/cart/deleteitem') }}';
                var token = getCsrf();

                btn.disabled = true;

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: new URLSearchParams({ id: id })
                })
                    .then(async function (res) {
                        // próbáljunk JSON-t olvasni, de fogadjuk el a "1"/"0" választ is
                        var out;
                        try { out = await res.json(); }
                        catch (_) { out = null; }

                        if ((out && out.status === 1) || out === 1 || out === '1') {
                            location.reload();
                        } else {
                            alert((out && out.message) ? out.message : 'Hiba a törlésnél.');
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        alert('Hálózati/CSRF hiba.');
                    })
                    .finally(function () { btn.disabled = false; });
            }, false);
        })();
    </script>
@endsection

<!-- MODAL_SELECTED_ADDONS--START -->
<div class="modal addons" id="modal_selected_addons" tabindex="-1" aria-labelledby="selected_addons_Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header justify-content-between">
                <p class="mb-0 fw-600 fs-5" id="addon_item_name"></p>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-0">
                <div class="mt-2 p-2 border-bottom d-none" id="addons">
                    <p class="m-0 fs-6 fw-500">{{ trans('labels.addons') }}</p>
                    <ul class="m-0 {{ session()->get('direction') == '2' ? 'pe-2' : 'ps-2' }}" id="item-addons"></ul>
                </div>
                <div class="mt-2 p-2 border-bottom d-none" id="extras">
                    <p class="m-0 fs-6 fw-500">{{ trans('labels.extras') }} </p>
                    <ul class="m-0 {{ session()->get('direction') == '2' ? 'pe-2' : 'ps-2' }}" id="item-extras"></ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- MODAL_SELECTED_ADDONS--END -->
