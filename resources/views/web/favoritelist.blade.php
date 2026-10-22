@extends('web.layout.default')
@section('page_title')
    | {{ trans('labels.favourite_list') }}
@endsection
@section('content')
    <div class="breadcrumb-sec">
        <div class="container">
            <div class="breadcrumb-sec-content">
                <nav class="text-dark breadcrumb-divider" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li
                            class="breadcrumb-item {{ session()->get('direction') == '2' ? 'breadcrumb-item-rtl ps-0' : '' }}">
                            <a class="text-dark fw-600" href="{{ route('home') }}">{{ trans('labels.home') }}</a>
                        </li>
                        <li class="breadcrumb-item {{ session()->get('direction') == '2' ? 'breadcrumb-item-rtl ps-0' : '' }} active"
                            aria-current="page">{{ trans('labels.favourite_list') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <section>
        <div class="container my-5 favourite">
            <div class="row">
                <div class="col-lg-3">
                    @include('web.layout.usersidebar')
                </div>
                <div class="col-lg-9 d-flex">
                    <div class="user-content-wrapper">
                        <p class="title border-bottom pb-3 mb-1">{{ trans('labels.favourite_list') }}</p>
                        @if (count($getfavoritelist) > 0)
                            <div class="row gx-sm-4 gx-0 gy-md-4 gy-3 menu m-0">
                                @foreach ($getfavoritelist as $itemdata)
                                    <div class="col-xl-4 col-lg-6 col-sm-6 col-xs-auto">
                                        <div class="card rounded-4 overflow-hidden h-100">
                                            <a href="{{ URL::to('item-' . $itemdata->slug) }}">
                                                <div class="card-image">
                                                    <img src="{{ $itemdata['item_image']->image_url }}"
                                                        class="card-img-top border-0 rounded-0 rounded-top position-relative"
                                                        alt="dishes">
                                                </div>
                                            </a>
                                            <div class="card-body pb-0 border-bottom">
                                                <div class="d-flex align-items-center mb-2 justify-content-between">
                                                    <div class="cat-name py-1 px-2 col-auto text-center">
                                                        <span>{{ $itemdata['category_info']->category_name }}</span>
                                                    </div>
                                                    @if (helper::reviews_enabled('product_review'))
                                                    <div class="">
                                                        <div class="d-flex fs-8 align-items-center">
                                                            <i class="fa-solid fa-star text-warning"></i>
                                                            <p
                                                                class="m-0 text-dark fw-500 {{ session()->get('direction') == '2' ? 'pe-1' : 'ps-1' }}">
                                                                {{ number_format($itemdata->avg_ratting, 1) }}</p>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                                <h5 class="item-card-title pb-3 fs-6 d-flex">
                                                    @if ($itemdata->item_type == 1)
                                                        <img src="{{ helper::image_path('veg.svg') }}" alt=""
                                                            class="{{ session()->get('direction') == '2' ? 'ms-1' : 'me-1' }}">
                                                    @else
                                                        <img src="{{ helper::image_path('nonveg.svg') }}" alt=""
                                                            class="{{ session()->get('direction') == '2' ? 'ms-1' : 'me-1' }}">
                                                    @endif
                                                    <div class="d-flex align-items-center gap-1">
                                                        <a href="{{ URL::to('item-' . $itemdata->slug) }}">
                                                            <p class="item-card-title mb-0 line-2 fs-7">
                                                                {{ $itemdata->item_name }}
                                                            </p>
                                                        </a>
                                                        @if ($itemdata->item_allergens != null)
                                                            <div type="button"
                                                                onclick="itemsallergens('{{ $itemdata->id }}','{{ route('get_item_allergens') }}')">
                                                                <div class="btn-info">
                                                                    <i class="fa-solid fa-info"></i>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </h5>
                                            </div>

                                            @php
                                                if ($itemdata->is_top_deals == 1) {
                                                    if (@$topdeals->offer_type == 1) {
                                                        if ($itemdata->item_price > @$topdeals->offer_amount) {
                                                            $price = $itemdata->item_price - @$topdeals->offer_amount;
                                                        } else {
                                                            $price = $itemdata->item_price;
                                                        }
                                                    } else {
                                                        $price =
                                                            $itemdata->item_price -
                                                            $itemdata->item_price * (@$topdeals->offer_amount / 100);
                                                    }
                                                    $original_price = $itemdata->item_price;
                                                    $off =
                                                        $original_price > 0
                                                            ? number_format(100 - ($price * 100) / $original_price, 1)
                                                            : 0;
                                                } else {
                                                    $price = $itemdata->item_price;
                                                    $original_price = $itemdata->original_price;
                                                    $off = $itemdata->discount_percentage;
                                                }
                                            @endphp
                                            @if ($off > 0)
                                                <div
                                                    class="offer-lable {{ session()->get('direction') == '2' ? 'rtl' : '' }}">
                                                    <h5>{{ $off }}% {{ trans('labels.off') }}
                                                    </h5>
                                                </div>
                                            @endif
                                            <div
                                                class="img-overlay {{ session()->get('direction') == '2' ? 'rtl' : '' }} set-fav-8">
                                                @if (Auth::user() && Auth::user()->type == 2)
                                                    @if ($itemdata->is_favorite == 1)
                                                        <a class="heart-icon bg-section-rgb-dark p-2 btn "
                                                            href="javascript:void(0)"
                                                            onclick="managefavorite('{{ $itemdata->id }}',0,'{{ URL::to('/managefavorite') }}','{{ request()->url() }}')"
                                                            title="Add to Wishlist">
                                                            <i class="fa-solid fa-heart fs-5"></i> </a>
                                                    @else
                                                        <a class="heart-icon bg-section-rgb-dark p-2 btn "
                                                            href="javascript:void(0)"
                                                            onclick="managefavorite('{{ $itemdata->id }}',1,'{{ URL::to('/managefavorite') }}','{{ request()->url() }}')"
                                                            title="Add to Wishlist">
                                                            <i class="fa-regular fa-heart fs-5"></i> </a>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="item-card-footer">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex justify-content-between align-items-center gap-1">
                                                        <span>{{ helper::currency_format($price) }}</span>
                                                        @if ($original_price > $price)
                                                            <del
                                                                class="text-muted">{{ helper::currency_format($original_price) }}</del>
                                                        @endif
                                                    </div>
                                                    @if ($itemdata->is_cart == 1)
                                                        <div class="item-quantity py-1 px-5">
                                                            <button type="button" class="btn btn-sm  fw-500"
                                                                onclick="removefromcart('{{ URL::to('/cart') }}','{{ trans('messages.remove_cartitem_note') }}','{{ trans('labels.goto_cart') }}')">-</button>
                                                            <input class="fw-500 item-total-qty-{{ $itemdata->slug }}"
                                                                type="text"
                                                                value="{{ helper::get_item_cart($itemdata->id) }}"
                                                                disabled />
                                                            <a class="btn btn-sm fw-500 border-0"
                                                                onclick="showitem('{{ $itemdata->slug }}','{{ URL::to('/show-item') }}')">+</a>
                                                        </div>
                                                    @else
                                                        <button
                                                            class="btn btn-sm btn-secondary fw-500 py-2 px-4 float-end rounded-3 d-flex gap-2 justify-content-center align-items-center addon_modal_{{ $itemdata->slug }}"
                                                            onclick="showitem('{{ $itemdata->slug }}','{{ URL::to('/show-item') }}')">{{ trans('labels.add') }}
                                                            <i
                                                                class="fa-solid fa-plus addon_modal_icon_{{ $itemdata->slug }}"></i>
                                                            <div
                                                                class="loader d-none addon_modal_loader_{{ $itemdata->slug }}">
                                                            </div>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-2 d-flex justify-content-center">
                                {{ $getfavoritelist->links() }}
                            </div>
                        @else
                            <div class="my-5 py-5">
                                @include('web.nodata')
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @include('web.subscribeform')
@endsection
