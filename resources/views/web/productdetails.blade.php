@extends('web.layout.default')
@section('page_title')
    | {{ @$getitemdata->item_name }}
@endsection
@section('content')
    <div class="breadcrumb-sec">
        <div class="container">
            <div class="breadcrumb-sec-content">
                <nav class="text-dark breadcrumb-divider" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a class="text-dark fw-600" href="{{ URL::to('/') }}">{{ trans('labels.home') }}</a>
                        </li>

                        {{-- ÚJ: Kategóriák link a lista oldalra --}}
                        <li class="breadcrumb-item">
                            <a class="text-dark fw-600" href="{{ url('categories') }}">{{ trans('labels.categories') }}</a>
                        </li>

                        {{-- Aktuális kategória neve --}}
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $currentCategory->category_name ?? \Illuminate\Support\Str::headline(str_replace('-', ' ', request('category'))) }}
                        </li>
                    </ol>

                </nav>
            </div>
        </div>
    </div>
    <section class="mt-5">
        <div class="container">
            <div class="item-details border-bottom pb-4">
                <div class="row mb-4 justify-content-between">
                    <div class="col-lg-5 col-12 mb-3">
                        <div class="item-img-cover">
                            {{-- image --}}

                            <div class="card h-100 overflow-hidden rounded-0 border-0 position-relative">
                                <!-- new big-view -->
                                <div class="sp-loading"><img src="https://via.placeholder.com/1100x1220" alt=""
                                        class="rounded-4"><br>LOADING IMAGES</div>
                                <div class="sp-wrap">
                                    @foreach ($getitemdata['item_images'] as $key => $firstimage)
                                        <a href="{{ @helper::image_path($firstimage->image_name) }}">
                                            <img src="{{ @helper::image_path($firstimage->image_name) }}" alt=""
                                                class="rounded-4"></a>
                                    @endforeach
                                </div>
                                <!-- new big-view -->
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 col-12">
                        <div class="item-content">
                            <div class="item-heading">
                                @php
                                    if ($getitemdata->is_top_deals == 1 && $topdeals != null) {
                                        if (@$topdeals->offer_type == 1) {
                                            if ($getitemdata->item_price > @$topdeals->offer_amount) {
                                                $price = $getitemdata->item_price - @$topdeals->offer_amount;
                                            } else {
                                                $price = $getitemdata->item_price;
                                            }
                                        } else {
                                            $price =
                                                $getitemdata->item_price -
                                                $getitemdata->item_price * (@$topdeals->offer_amount / 100);
                                        }
                                        $original_price = $getitemdata->item_price;
                                        $off =
                                            $original_price > 0
                                                ? number_format(100 - ($price * 100) / $original_price, 1)
                                                : 0;
                                    } else {
                                        $price = $getitemdata->item_price;
                                        $original_price = $getitemdata->original_price;
                                        $off = $getitemdata->discount_percentage;
                                    }
                                @endphp
                                @if ($off > 0)
                                    <div class="my-2">
                                        <span class="py-1 px-2 mb-2 offer-badge">{{ $off }}%
                                            {{ trans('labels.off') }}</span>
                                    </div>
                                @endif
                                <div>
                                    <span
                                        class="text-muted fs-7 fw-500">{{ $getitemdata['category_info']->category_name }}</span>
                                </div>
                                <div class="d-flex text-align-center mt-2">
                                    <img class="col-1 {{ session()->get('direction') == 2 ? 'ms-1' : 'me-1' }}"
                                        @if ($getitemdata->item_type == 1) src="{{ helper::image_path('veg.svg') }}" @else src="{{ helper::image_path('nonveg.svg') }}" @endif
                                        alt="">
                                    <span class="item-title">{{ $getitemdata->item_name }}</span>
                                </div>
                            </div>
                            <div class="row my-2 align-items-center">
                                <div class="d-flex flex-wrap justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <p class="item-price item_price m-0 text-black subtotal_{{ $getitemdata['id'] }}">
                                            {{ helper::currency_format($price) }}</p>
                                        @if ($original_price > $price)
                                            <del class="item-price item_price fs-7 text-muted">
                                                {{ helper::currency_format($original_price) }}</del>
                                        @endif
                                    </div>
                                    <a href="#review-tab">
                                        <div class="d-flex align-items-center">
                                            <p class="fs-8 mb-0"><i
                                                    class="text-warning fa-solid fa-star {{ session()->get('direction') == '2' ? 'ps-1' : 'pe-1' }}"></i><span
                                                    class="text-dark fw-500">{{ number_format($getitemdata->avg_ratting, 1) }}</span>
                                            </p>
                                            <span
                                                class="px-2 d-inline-block fs-8 text-muted fw-500">({{ count($itemreviewdata) }}
                                                {{ trans('labels.reviews') }})</span>
                                        </div>
                                    </a>
                                </div>

                            </div>

                            @if (@Helper::checkaddons('fake_view'))
                                @if(Helper::appdata()->product_fake_view == 1)
                                @php

                                $var = ["{eye}", "{count}"];
                                $newvar   = ["<i class='fa-solid fa-eye'></i>", rand(Helper::appdata()->min_view_count,Helper::appdata()->max_view_count)];

                                $fake_view = str_replace($var, $newvar, Helper::appdata()->fake_view_message);
                                @endphp
                                <div class="d-flex gap-1 align-items-center blink_me mb-2">
                                    <p class="fw-600 text-success m-0">{!!$fake_view!!}</p>
                                </div>
                                @endif
                            @endif

                            <div class="d-flex pb-2 border-bottom">
                                <div class="col-auto">
                                    @if ($getitemdata->tax != '' && $getitemdata->tax != 0)
                                        <span class="text-danger float-end">{{ trans('labels.exclusive_taxes') }}</span>
                                    @else
                                        <span class="text-danger float-end">{{ trans('labels.inclusive_taxes') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($getitemdata->is_top_deals == 1 && $topdeals != null)
                                <h5 class="mt-3">⏰ {{ trans('labels.hurry_up') }}</h5>
                                <div class="product-detail-countdown d-flex border-bottom gap-2 my-3 pb-3" id="countdown">
                                </div>
                            @endif
                            @if (!empty($getitemdata->addons_group) && count($getitemdata->addons_group) > 0)
                                <div class="row align-items-center">
                                    <div class="col-lg-12 col-md-12 col-sm-12 m-auto">
                                        <div class="addon-item-details scroll-addon-details">
                                            @foreach ($getitemdata['addons_group'] as $addons_group)
                                                @php
                                                    $availableAddons = collect($getitemdata['addons'])->where(
                                                        'addongroup_id',
                                                        $addons_group->id,
                                                    );
                                                @endphp
                                                @if ($availableAddons->isNotEmpty())
                                                    <div class="item-addons-list mt-3 border-bottom pb-3"
                                                        id="item_addons_group_{{ $getitemdata['id'] }}_{{ $addons_group->id }}">
                                                        <h5 class="mb-1 fs-6">{{ $addons_group->name }}</h5>
                                                        <div class="d-flex align-items-center gap-1 pb-1">
                                                            @if ($addons_group->selection_type == 1)
                                                                <i class="fa-solid fa-triangle-exclamation addon_group_color fs-8"
                                                                    id="addon_required_icon_{{ $addons_group->id }}_{{ $getitemdata['id'] }}"></i>
                                                                <span class="fs-8 fw-600 addon_group_color"
                                                                    id="addon_required_text_{{ $addons_group->id }}_{{ $getitemdata['id'] }}">{{ trans('labels.required') }}</span>
                                                                <span class="fs-8">•</span>
                                                            @elseif ($addons_group->selection_type == 2)
                                                                <span
                                                                    class="fs-8">({{ trans('labels.optional') }})</span>
                                                                <span class="fs-8">•</span>
                                                            @endif
                                                            @if ($addons_group->selection_count == 1)
                                                                <span class="fs-8">{{ trans('labels.select') }}
                                                                    1</span>
                                                            @else
                                                                <span class="fs-8">{{ trans('labels.min') }}
                                                                    {{ $addons_group->min_count }} |
                                                                    {{ trans('labels.max') }}
                                                                    {{ $addons_group->max_count }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @foreach ($getitemdata['addons'] as $addons)
                                                            @if ($addons->addongroup_id == $addons_group->id)
                                                                <div
                                                                    class="mx-2 {{ session()->get('direction') == '2' ? 'd-flex gap-2' : 'form-check' }}">
                                                                    @php
                                                                        if ($addons_group->selection_count == 1) {
                                                                            $type = 'radio';
                                                                        } elseif ($addons_group->selection_count == 2) {
                                                                            $type = 'checkbox';
                                                                        }
                                                                    @endphp
                                                                    <input
                                                                        class="form-check-input cursor-pointer addons_chk_{{ $getitemdata['id'] }} {{ session()->get('direction') == '2' ? 'ms-0' : '' }}"
                                                                        type="{{ $type }}"
                                                                        value="{{ $addons->id }}"
                                                                        data-addons-id="{{ $addons->id }}"
                                                                        data-addons-price="{{ $addons->price }}"
                                                                        data-addons-name="{{ $addons->name }}"
                                                                        onclick="getaddons('{{ $getitemdata['id'] }}')"
                                                                        name="addons_id_{{ $addons_group->id }}_{{ $getitemdata['id'] }}"
                                                                        id="addons_{{ $addons_group->id }}_{{ $getitemdata['id'] }}_{{ $addons->id }}">
                                                                    <div
                                                                        class="d-flex justify-content-between w-100 {{ session()->get('direction') == '2' ? 'ps-2' : 'pe-2' }}">
                                                                        <label class="form-check-label cursor-pointer fs-7"
                                                                            for="addons_{{ $addons_group->id }}_{{ $getitemdata['id'] }}_{{ $addons->id }}">{{ $addons->name }}</label>
                                                                        <label class="form-check-label cursor-pointer fs-7"
                                                                            for="addons_{{ $addons_group->id }}_{{ $getitemdata['id'] }}_{{ $addons->id }}">
                                                                            {{ helper::currency_format($addons->price) }}
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                        @if ($addons_group->selection_type == 1)
                                                            <span
                                                                class="addons_error_{{ $addons_group->id }}_{{ $getitemdata['id'] }} text-danger fs-7 ms-2"></span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                            {{-- Item Extra --}}
                            @if (!empty($getitemdata->extras) && count($getitemdata->extras) > 0)
                                <div class="item-details mt-3">
                                    <h5 class="mb-1 fs-6">{{ trans('labels.extras') }}</h5>
                                    <div class="item-addons-list mt-2 border-bottom pb-3 px-2">
                                        @foreach ($getitemdata->extras as $extras)
                                            <div
                                                class="{{ session()->get('direction') == '2' ? 'd-flex' : 'form-check' }}">
                                                <input
                                                    class="form-check-input cursor-pointer extras_chk_{{ $getitemdata['id'] }} {{ session()->get('direction') == '2' ? 'ms-0' : '' }}"
                                                    type="checkbox" value="{{ $extras->id }}"
                                                    data-extras-id="{{ $extras->id }}"
                                                    data-extras-price="{{ $extras->price }}"
                                                    data-extras-name="{{ $extras->name }}"
                                                    id="extras_{{ $extras->id }}_{{ $getitemdata['id'] }}"
                                                    name="extras_id_{{ $getitemdata['id'] }}">
                                                <div
                                                    class="d-flex justify-content-between align-items-center w-100 text-black">
                                                    <label class="form-check-label cursor-pointer me-2 fs-7"
                                                        for="extras_{{ $extras->id }}_{{ $getitemdata['id'] }}">{{ $extras->name }}</label>
                                                    <label class="form-check-label cursor-pointer me-2 fs-7"
                                                        for="extras_{{ $extras->id }}_{{ $getitemdata['id'] }}">
                                                        {{ helper::currency_format($extras->price) }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            <input type="hidden" name="addongroup" id="addongroup_{{ $getitemdata['id'] }}"
                                data-addongroup_val="{{ $getitemdata['addons_group'] }}">
                            <input type="hidden" name="slug" id="slug_{{ $getitemdata['id'] }}"
                                value="{{ $getitemdata['slug'] }}">
                            <input type="hidden" name="item_name" id="item_name_{{ $getitemdata['id'] }}"
                                value="{{ $getitemdata['item_name'] }}">
                            <input type="hidden" name="item_type" id="item_type_{{ $getitemdata['id'] }}"
                                value="{{ $getitemdata['item_type'] }}">
                            <input type="hidden" name="image_name" id="image_name_{{ $getitemdata['id'] }}"
                                value="{{ $getitemdata['item_image']->image_name }}">
                            <input type="hidden" name="tax" id="item_tax_{{ $getitemdata['id'] }}"
                                value="{{ $getitemdata['tax'] }}">
                            <input type="hidden" name="item_price" id="item_price_{{ $getitemdata['id'] }}"
                                value="{{ $price }}">
                            <input type="hidden" name="request_url" id="request_url_{{ $getitemdata['slug'] }}"
                                value="{{ request()->segments()[0] }}">
                            <input type="hidden" name="login_required" id="login_required_{{ $getitemdata['slug'] }}"
                                value="{{ helper::appdata()->login_required }}">
                            <input type="hidden" name="checklogin" id="checklogin_{{ $getitemdata['slug'] }}"
                                value="{{ Auth::user() && Auth::user()->type == 2 }}">
                            <input type="hidden" name="customer_login" id="customer_login_{{ $getitemdata['slug'] }}"
                                value="{{ App\Models\SystemAddons::where('unique_identifier', 'customer_login')->first() }}">
                            <div class="border-bottom border-top py-3">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-auto col-12">
                                        <div class="d-flex align-items-center gap-2">
                                            <div>
                                                <p class="mb-0">{{ trans('labels.quantity') }} :</p>
                                            </div>
                                            <div class="btn item-quantity">
                                                <button class="btn btn-sm item-quantity-minus"
                                                    onclick="changeqty('{{ $getitemdata['slug'] }}','minus')">-</button>
                                                <input class="item-quantity-input" type="text" value="1"
                                                    readonly="" id="item_qty_{{ $getitemdata['slug'] }}">
                                                <button class="btn btn-sm item-quantity-plus"
                                                    onclick="changeqty('{{ $getitemdata['slug'] }}','plus')">+</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6 col-6">
                                        <button
                                            class="btn btn-secondary w-100 m-0 fs-7 fw-500 rounded-3 d-flex gap-3 justify-content-center align-items-center cart"
                                            onclick="addtocart('{{ URL::to('addtocart') }}','{{ $getitemdata['id'] }}','0')">
                                            {{ trans('labels.add_to_cart') }}
                                            <div class="loader d-none cart_loader"></div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 col-sm-6 col-6">
                                        @if (@helper::checkaddons('customer_login'))
                                            @if (helper::appdata()->login_required == 1)
                                                <button
                                                    class="btn btn-primary w-100 m-0 fs-7 fw-500 rounded-3 d-flex gap-3 justify-content-center align-items-center quick_order"
                                                    @if (helper::appdata()->is_checkout_login_required == 1) onclick="showlogin()" @else  onclick="addtocart('{{ URL::to('addtocart') }}','{{ $getitemdata['id'] }}','1')" @endif>
                                                    {{ trans('labels.quick_order') }}
                                                    <div class="loader d-none quick_order_loader"></div>
                                                </button>
                                            @else
                                                <button
                                                    class="btn btn-primary w-100 m-0 fs-7 fw-500 rounded-3 d-flex gap-3 justify-content-center align-items-center quick_order"
                                                    onclick="addtocart('{{ URL::to('addtocart') }}','{{ $getitemdata['id'] }}','1')">
                                                    {{ trans('labels.quick_order') }}
                                                    <div class="loader d-none quick_order_loader"></div>
                                                </button>
                                            @endif
                                        @else
                                            <button
                                                class="btn btn-primary w-100 m-0 fs-7 fw-500 rounded-3 d-flex gap-3 justify-content-center align-items-center quick_order"
                                                onclick="addtocart('{{ URL::to('addtocart') }}','{{ $getitemdata['id'] }}','1')">
                                                {{ trans('labels.quick_order') }}
                                                <div class="loader d-none quick_order_loader"></div>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between mt-3 align-items-center">
                                <div class="col-sm-6">
                                    <div class="wishlist set-fav-{{ $getitemdata->id }}">
                                        @if ($getitemdata->is_favorite == 1)
                                            <a class="text-dark fw-600 fs-7 d-flex gap-1 py-2 align-items-center"
                                                @if (Auth::user() && Auth::user()->type == 2) href="javascript:void(0)" onclick="managefavorite('{{ $getitemdata->id }}',0,'{{ URL::to('/managefavorite') }}')" @else href="{{ URL::to('/login') }}" @endif>
                                                <i class="fa-solid fa-heart fs-6"></i>
                                                {{ trans('labels.remove_wishlist') }}
                                            </a>
                                        @else
                                            <a class="text-dark fw-600 fs-7 d-flex gap-1 py-2 align-items-center"
                                                @if (Auth::user() && Auth::user()->type == 2) href="javascript:void(0)" onclick="managefavorite('{{ $getitemdata->id }}',1,'{{ URL::to('/managefavorite') }}')" @else href="{{ URL::to('/login') }}" @endif>
                                                <i class="fa-regular fa-heart fs-6"></i>
                                                {{ trans('labels.add_wishlist') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        @if (helper::appdata()->google_review_url != '')
                                            <a href="{{ helper::appdata()->google_review_url }}"
                                                class="icon-box" target="_blank"
                                                tooltip="{{ trans('labels.review') }}">
                                                <i class="fa-solid fa-star fs-8"></i>
                                            </a>
                                        @endif
                                        @if (helper::appdata()->mobile != '')
                                            <a href="callto:{{ helper::appdata()->mobile }}"
                                                tooltip="{{ trans('labels.call') }}" class="icon-box">
                                                <i class="fa-solid fa-phone fs-8"></i>
                                            </a>
                                        @endif
                                        @if (@helper::checkaddons('whatsapp_message'))
                                            @if (whatsapp_helper::whatsapp_message_config()->whatsapp_number != '')
                                                <a href="https://api.whatsapp.com/send?phone={{ whatsapp_helper::whatsapp_message_config()->whatsapp_number }}'&text={{ $getitemdata->item_name }}"
                                                    target="_blank" tooltip="{{ trans('labels.whatsapp') }}"
                                                    class="icon-box">
                                                    <i class="fa-brands fa-whatsapp fs-8"></i>
                                                </a>
                                            @endif
                                        @endif
                                        @if ($getitemdata->video_url != '')
                                            <a href="{{ $getitemdata->video_url }}" target="_blank"
                                                tooltip="{{ trans('labels.video') }}" class="icon-box">
                                                <i class="fa-solid fa-video fs-8"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="product-view" id="review-tab">
                    <ul class="nav nav-pills py-3 mb-4 border-bottom border-top" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ session()->get('direction') == 2 ? 'ms-2 ms-md-3' : 'me-2 me-md-3' }} active"
                               aria-current="page" data-bs-toggle="pill" data-bs-target="#pills-description"
                               href="javascript:void(0)" aria-selected="true" role="tab">
                                {{ trans('labels.description') }}
                            </a>
                        </li>
                        {{-- ide jöhetnek további tabok (vélemények stb.) --}}
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <div class="tab-pane fade show active" id="pills-description" role="tabpanel">
                            @php
                                // Allergének: HTML eltávolítása és normalizálás
                                $raw = strip_tags((string)($getitemdata->item_allergens ?? ''));
                                $raw = str_replace([';', ' '], [',', ''], $raw);
                                $parts = array_filter(array_map('trim', explode(',', $raw)), fn($x) => $x !== '');
                                $displayAllergens = implode(',', $parts);
                            @endphp

                            {{-- csak akkor jelenjen meg, ha van allergén --}}
                            @if ($displayAllergens !== '')
                                <div class="d-flex align-items-center flex-wrap gap-2 mt-2">
                                    <a href="javascript:void(0)"
                                       class="d-inline-flex align-items-center gap-2 text-decoration-none"
                                       onclick="itemsallergens('{{ $getitemdata->id }}','{{ route('get_item_allergens') }}')"
                                       aria-label="Allergének megnyitása">
                        <span class="btn btn-sm btn-outline-info p-1 lh-1">
                            <i class="fa-solid fa-info"></i>
                        </span>
                                        <span class="fw-600">{{ trans('labels.allergens') }}:</span>
                                    </a>
                                    <span class="text-muted">{{ $displayAllergens }}</span>
                                    <a href="{{ url('alergens.html') }}" class="ms-2 text-decoration-underline small">
                                        {{ __('allergén táblázat') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>




            @if (@helper::checkaddons('product_review'))
                            @if (@helper::checkaddons('customer_login'))
                                @if (helper::appdata()->login_required == 1)
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" href="javascript:void(0)" data-bs-toggle="pill"
                                            data-bs-target="#pills-review" aria-selected="false" role="tab"
                                            tabindex="-1">{{ trans('labels.reviews') }}</a>
                                    </li>
                                @endif
                            @endif
                        @endif
                    </ul>
                </div>

                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade active show" id="pills-description" role="tabpanel"
                        aria-labelledby="pills-description-tab">
                        <div class="row mt-2">
                            <div class="col-auto">
                                <h4 class="heading mb-3 fw-600 text-dark text-truncate">
                                    {{ trans('labels.description') }}
                                </h4>
                                @php
                                    $descriptionWithGlossary = preg_replace_callback(
                                        '/\[\[([^|\]]+)\|([^\]]+)\]\]/u',
                                        static function ($matches) {
                                            $term = trim($matches[1]);
                                            $meaning = trim($matches[2]);

                                            return sprintf(
                                                '<span class="foreign-term" tabindex="0" role="button" data-bs-toggle="tooltip" data-bs-trigger="hover focus click" title="%s">%s</span>',
                                                e($meaning),
                                                e($term),
                                            );
                                        },
                                        $getitemdata->item_description ?? '',
                                    );
                                @endphp
                                <div class="item-description">
                                    <p class="text-justify mb-0">{!! $descriptionWithGlossary !!}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (@helper::checkaddons('product_review'))
                        @if (@helper::checkaddons('customer_login'))
                            @if (helper::appdata()->login_required == 1)
                                <div class="tab-pane fade" id="pills-review" role="tabpanel"
                                    aria-labelledby="pills-review-tab">
                                    <div class="row gx-4 gx-xxl-5 gy-md-0 gy-4">
                                        <div class="col-md-12 col-lg-7 col-xxl-6">
                                            <!-- Customer rating -->
                                            <h4 class="heading mb-3 fw-600 text-dark text-truncate">
                                                {{ trans('labels.customer_rating') }}
                                            </h4>
                                            <div class="card border-0 bg-gray rounded-3 p-4 mb-4 rounded-3">
                                                <div class="row g-4 align-items-center">
                                                    <!-- Rating info -->
                                                    <div class="col-md-4">
                                                        <div class="text-center">
                                                            <!-- Info -->
                                                            <h2 class="mb-0 fw-bold text-dark">
                                                                <i class="fa-solid fa-star text-warning"></i>
                                                                {{ number_format($getitemdata->avg_ratting, 1) }}
                                                            </h2>
                                                            <p class="mb-2 text-muted">{{ trans('labels.based_on') }}
                                                                {{ count($itemreviewdata) }}
                                                                {{ trans('labels.reviews') }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Progress-bar START -->
                                                    <div class="col-md-8">
                                                        <div class="card-body p-0">
                                                            <div class="row gx-3 g-2 align-items-center">
                                                                <!-- 5.0 Progress bar and Rating -->
                                                                <div class="col-2 col-sm-2 text-end">
                                                                    <span class="h6 fw-semibold mb-0 text-dark">5.0</span>
                                                                </div>
                                                                @php
                                                                    if (count(@$itemreviewdata) != 0) {
                                                                        $five =
                                                                            ($fivestaraverage /
                                                                                count(@$itemreviewdata)) *
                                                                            100;
                                                                    } else {
                                                                        $five = 0;
                                                                    }
                                                                @endphp
                                                                <div class="col-2 col-sm-8">
                                                                    <div class="progress progress-sm">
                                                                        <div class="progress-bar" role="progressbar"
                                                                            style="width: {{ $five }}%"
                                                                            aria-valuenow="{{ round($five) }}%"
                                                                            aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- 5.0 Percentage -->
                                                                <div
                                                                    class="col-2 col-sm-2 {{ session()->get('direction') == 2 ? 'text-start' : 'text-end' }}">
                                                                    <span
                                                                        class="h6 fw-semibold mb-0 text-dark">{{ round($five) }}%</span>
                                                                </div>

                                                                <!-- 4.0 Progress bar and Rating -->
                                                                <div class="col-2 col-sm-2 text-end">
                                                                    <span class="h6 fw-semibold mb-0 text-dark">4.0</span>
                                                                </div>
                                                                @php
                                                                    if (count(@$itemreviewdata) != 0) {
                                                                        $four =
                                                                            ($fourstaraverage /
                                                                                count(@$itemreviewdata)) *
                                                                            100;
                                                                    } else {
                                                                        $four = 0;
                                                                    }
                                                                @endphp
                                                                <div class="col-8 col-sm-8">
                                                                    <div class="progress progress-sm">
                                                                        <div class="progress-bar" role="progressbar"
                                                                            style="width: {{ $four }}%"
                                                                            aria-valuenow="{{ round($four) }}%"
                                                                            aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- 4.0 Percentage -->
                                                                <div
                                                                    class="col-2 col-sm-2 {{ session()->get('direction') == 2 ? 'text-start' : 'text-end' }}">
                                                                    <span
                                                                        class="h6 fw-semibold mb-0 text-dark">{{ round($four) }}%</span>
                                                                </div>

                                                                <!-- 3.0 Progress bar and Rating -->
                                                                <div class="col-2 col-sm-2 text-end">
                                                                    <span class="h6 fw-semibold mb-0 text-dark">3.0</span>
                                                                </div>
                                                                @php
                                                                    if (count(@$itemreviewdata) != 0) {
                                                                        $three =
                                                                            ($threestaraverage /
                                                                                count(@$itemreviewdata)) *
                                                                            100;
                                                                    } else {
                                                                        $three = 0;
                                                                    }
                                                                @endphp
                                                                <div class="col-8 col-sm-8">
                                                                    <div class="progress progress-sm">
                                                                        <div class="progress-bar" role="progressbar"
                                                                            style="width: {{ $three }}%"
                                                                            aria-valuenow="{{ round($three) }}%"
                                                                            aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- 3.0 Percentage -->
                                                                <div
                                                                    class="col-2 col-sm-2 {{ session()->get('direction') == 2 ? 'text-start' : 'text-end' }}">
                                                                    <span
                                                                        class="h6 fw-semibold mb-0 text-dark">{{ round($three) }}%</span>
                                                                </div>

                                                                <!-- 2.0 Progress bar and Rating -->
                                                                <div class="col-2 col-sm-2 text-end">
                                                                    <span class="h6 fw-semibold mb-0 text-dark">2.0</span>
                                                                </div>
                                                                @php
                                                                    if (count(@$itemreviewdata) != 0) {
                                                                        $two =
                                                                            ($twostaraverage /
                                                                                count(@$itemreviewdata)) *
                                                                            100;
                                                                    } else {
                                                                        $two = 0;
                                                                    }
                                                                @endphp
                                                                <div class="col-8 col-sm-8">
                                                                    <div class="progress progress-sm">
                                                                        <div class="progress-bar" role="progressbar"
                                                                            style="width: {{ $two }}%"
                                                                            aria-valuenow="{{ round($two) }}%"
                                                                            aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- 2.0 Percentage -->
                                                                <div
                                                                    class="col-2 col-sm-2 {{ session()->get('direction') == 2 ? 'text-start' : 'text-end' }}">
                                                                    <span
                                                                        class="h6 fw-semibold mb-0 text-dark">{{ round($two) }}%</span>
                                                                </div>

                                                                <!-- 1.0 Progress bar and Rating -->
                                                                <div class="col-2 col-sm-2 text-end">
                                                                    <span class="h6 fw-semibold mb-0 text-dark">1.0</span>
                                                                </div>
                                                                @php
                                                                    if (count(@$itemreviewdata) != 0) {
                                                                        $one =
                                                                            ($onestaraverage /
                                                                                count(@$itemreviewdata)) *
                                                                            100;
                                                                    } else {
                                                                        $one = 0;
                                                                    }
                                                                @endphp
                                                                <div class="col-8 col-sm-8">
                                                                    <div class="progress progress-sm">
                                                                        <div class="progress-bar" role="progressbar"
                                                                            style="width: {{ $one }}%"
                                                                            aria-valuenow="{{ round($one) }}%"
                                                                            aria-valuemin="0" aria-valuemax="100">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <!-- 1.0 Percentage -->
                                                                <div
                                                                    class="col-2 col-sm-2 {{ session()->get('direction') == 2 ? 'text-start' : 'text-end' }}">
                                                                    <span
                                                                        class="h6 fw-semibold mb-0 text-dark">{{ round($one) }}%</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Progress-bar END -->
                                                </div>
                                            </div>
                                            <!-- Customer rating -->
                                            <div class="d-grid justify-items-center mt-4 mb-3">
                                                @if (Auth::user() && Auth::user()->type == 2)
                                                    <button class="btn btn-primary fs-7 write-review"
                                                        data-item-id="{{ $getitemdata->id }}"
                                                        data-item-name="{{ $getitemdata->item_name }}"
                                                        data-item-image="{{ helper::image_path($getitemdata['item_image']->image_name) }}">{{ trans('labels.write_review') }}</button>
                                                @else
                                                    <button class="btn btn-primary fs-7"
                                                        onclick="showlogin()">{{ trans('labels.write_review') }}</button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Customer Review -->
                                        <div class="col-md-12 col-lg-5 col-xxl-6">
                                            <h4 class="heading mb-3 fw-600 text-dark text-truncate">
                                                {{ trans('labels.customer_review') }}
                                            </h4>
                                            @if (count($itemreviewdata) > 0)
                                                @foreach ($itemreviewdata as $reviewdata)
                                                    <div class="py-2">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="d-md-flex align-items-center">
                                                                    <!-- review avatar -->
                                                                    <div
                                                                        class="avatar avatar-lg mb-md-0 mb-2 flex-shrink-0 {{ session()->get('direction') == 2 ? ' ms-sm-3' : 'me-sm-3' }}">
                                                                        <img class="avatar-img rounded-circle w-100 h-100 object-fit-cover"
                                                                            src="{{ $reviewdata->user_info->profile_image }}"
                                                                            alt="avatar">
                                                                    </div>
                                                                    <!-- review avatar -->

                                                                    <!-- review-content -->
                                                                    <div class="w-100">
                                                                        <div
                                                                            class="d-flex flex-wrap gap-2 justify-content-between mt-1 mt-md-0 mb-2">
                                                                            <div>
                                                                                <h6 class="mb-0 fw-600">
                                                                                    {{ $reviewdata->user_info->name }}
                                                                                </h6>
                                                                                <!-- Info -->
                                                                                <p
                                                                                    class="text-muted fs-8 fw-500 mt-1 mb-0">
                                                                                    {{ helper::date_format($reviewdata->created_at) }}
                                                                                </p>
                                                                            </div>
                                                                            <!-- Review star -->
                                                                            <span class="fw-600 fs-6">
                                                                                <i
                                                                                    class="fas fa-star fa-fw text-warning fs-7"></i>
                                                                                {{ $reviewdata->ratting > 0 ? number_format($reviewdata->ratting, 1) : 0 }}</span>
                                                                        </div>

                                                                        <p class="text-muted fs-7 fw-normal line-2 mb-0 ">
                                                                            {{ $reviewdata->comment }}
                                                                        </p>
                                                                    </div>
                                                                    <!-- review-content -->
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                @include('web.nodata')
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!-- RELATED PRODUCTS Section Start Here -->
    @if (count($getrelateditems) > 0)
        <section class="menu py-5 m-0">
            <div class="container">
                <div class="row align-items-center justify-content-between mb-2 px-2">
                    <div class="col-auto menu-heading p-1">
                        <h2 class="text-capitalize fs-2 fw-600">
                            {{ trans('labels.related_items') }}</h2>
                    </div>
                    <div class="col-auto px-1 pb-2"><a
                            href="{{ URL::to('menu?category=' . $getitemdata['category_info']->slug) }}"
                            class="btn btn-outline-primary px-4 py-2">{{ trans('labels.view_all') }}</a>
                    </div>
                </div>
                <div class="row g-4">
                    @foreach ($getrelateditems as $itemdata)
                        @include('web.home1.itemview')
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- RELATED PRODUCTS Section End Here -->
    @include('web.subscribeform')
@endsection
@section('scripts')
    <style>
        .item-description .foreign-term {
            text-decoration: underline dotted;
            text-underline-offset: 2px;
            cursor: help;
            font-weight: 600;
        }
    </style>

    <script src="{{ url(env('ASSETSPATHURL') . 'web-assets/js/item-image-carousel/main.js') }}"></script>
    <script src="{{ url(env('ASSETSPATHURL') . 'web-assets/js/item-image-carousel/zoom-image.js') }}"></script>

    <script src="{{ url(env('ASSETSPATHURL') . 'web-assets/js/smoothproducts.js') }}"></script>
    <script>
        $('.sp-wrap').smoothproducts();
        window.onload = function() {
            getaddons("{{ $getitemdata['id'] }}"); // Call the function
        };

        var topdeals = "{{ $getitemdata->is_top_deals == 1 ? 1 : 0 }}";

        $(".write-review").click(function() {
            $("#data-item-name").text($(this).attr('data-item-name'));
            $("#data-item-id").val($(this).attr('data-item-id'));
            $("#reviewModal img").attr('src', $(this).attr('data-item-image'));
            $('#reviewModal').modal('show');
        });

        if (typeof bootstrap !== "undefined" && typeof bootstrap.Tooltip !== "undefined") {
            document.querySelectorAll('.foreign-term[data-bs-toggle="tooltip"]').forEach(function(element) {
                bootstrap.Tooltip.getOrCreateInstance(element);
            });
        }
    </script>
@endsection
