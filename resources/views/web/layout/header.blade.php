<!-- header section start -->
<header>

    <div class="header-bar" id="header1">
        <nav class="navbar navbar-expand-lg sticky-top p-0">
            <div class="container navbar-container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img class="img-resposive img-fluid city-brand-logo" src="{{ url('public/city/logo.webp') }}" width="118" height="84"
                         alt="Gyros City">
                </a>
                <!-- language-btn -->
                @if (@helper::checkaddons('language'))
                    <div class="buttons d-flex align-items-center">
                        <div class="dropdown d-block d-lg-none">
                            <a class="btn text-white dropdown px-1 fs-6 border-0 header-box" type="button"
                               id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-globe fs-5"></i></a>
                            <ul class="dropdown-menu {{ session()->get('direction') == '2' ? 'min-dropdown-rtl' : 'min-dropdown' }}"
                                aria-labelledby="dropdownMenuButton1">
                                @foreach (helper::language() as $lang)
                                    <li>
                                        <a class="dropdown-item text-dark d-flex gap-2"
                                           href="{{ URL::to('/language-' . $lang->code) }}">
                                            <img src="{{ helper::image_path($lang->image) }}"
                                                 class="img-fluid lag-img rounded-5" alt="">{{ $lang->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                    </div>
                @endif
                <!-- language-btn -->

                {{-- for large devices - for header bar --}}
                <div class="navbar-collapse collapse">
                    <div class="navbar-nav mx-auto">
                        <a class="nav-link px-3 {{ request()->is('/') ? 'active' : '' }}"
                           href="{{ route('home') }}">{{ trans('labels.home') }}</a>
                        <a class="nav-link px-3 {{ request()->is('categories', 'menu', 'item-*') ? 'active' : '' }}"
                           href="{{ route('categories') }}">{{ trans('labels.menu') }}</a>

                        <a class="nav-link px-3 {{ request()->is('faq') ? 'active' : '' }}"
                           href="{{ route('faq') }}">{{ trans('labels.faq') }}</a>
                        <a class="nav-link px-3 {{ request()->is('contactus') ? 'active' : '' }}"
                           href="{{ route('contact-us') }} ">{{ trans('labels.help_contact_us') }}</a>



                    </div>
                    <div class="d-flex gap-3 align-items-center nav-sidebar-d-none">
                        <!-- language-btn -->
                        @if (@helper::checkaddons('language'))
                            <div class="lag dropdown">
                                <a class="header-box" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                                   aria-expanded="false"><img src="{{ helper::image_path(Session::get('flag')) }}"
                                                              class="img-fluid lag-img rounded-5" alt=""></a>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    @foreach (helper::language() as $lang)
                                        <li>
                                            <a class="dropdown-item text-dark d-flex gap-2"
                                               href="{{ URL::to('/language-' . $lang->code) }}"><img
                                                    src="{{ helper::image_path($lang->image) }}"
                                                    class="img-fluid lag-img rounded-5"
                                                    alt="">{{ $lang->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="header-search header-box">
                            <input type="text" class="search-form" placeholder="{{ trans('labels.search_here') }}"
                                   required>
                            @if (session()->get('direction') == '')
                                <a href="{{ route('search') }}" class="search-button">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                            @elseif (session()->get('direction') == '2')
                                <a href="{{ route('search') }}" class="search-button">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                            @else
                                <a href="{{ route('search') }}" class="search-button">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </a>
                            @endif
                        </div>
                        <!-- cart-btn -->
                        <div class="cart-area header-box">
                            <a href="{{ route('cart') }}" class="text-white">
                                <i class="fa-solid fa-cart-shopping"></i>
                                <span class="cart-badge">{{ helper::get_user_cart() }}</span>
                            </a>
                        </div>

                        <!-- user-btn -->
                        <div class="header-box ">
                            @if (Auth::user() && Auth::user()->type == 2)
                                <a class="nav-link text-white" href="{{ route('user-profile') }}" role="button">
                                    <i class="fa-solid fa-user"></i>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="text-white"><i class="fa-solid fa-user"></i></a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>
<!-- header section end -->

<!-- offer btn start-->
<div class="{{ session()->get('direction') == '2' ? 'rtl-buttons' : 'ltr-buttons' }}">
    @if (@helper::checkaddons('coupon'))
        @if (!empty(helper::getoffers()) && count(helper::getoffers()) > 0)
            <button class="btn btn-primary offer-button" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasOffer" aria-controls="offcanvasOffer">
                <i class="fa-sharp fa-solid fa-badge-percent"></i> {{ trans('labels.offers') }}
            </button>
        @endif
    @endif
</div>
<div class="offer">
    <div class="offcanvas {{ session()->get('direction') == '2' ? 'offcanvas-start' : 'offcanvas-end' }}"
         tabindex="-1" id="offcanvasOffer" aria-labelledby="offcanvasOfferLabel">
        <div class="offcanvas-header border-bottom bg-light">
            <div class="d-flex d-grid gap-2 align-items-center">
                <i class="fa-sharp fa-solid fa-badge-percent"></i>
                <h5 class="offcanvas-title fw-600" id="offcanvasOfferLabel">{{ trans('labels.offers') }}</h5>
            </div>
            <button type="button"
                    class="btn-close {{ session()->get('direction') == '2' ? 'me-auto ms-0' : 'ms-auto me-0' }}"
                    data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="row g-3">
                @foreach (helper::getoffers() as $offers)
                    @php
                        $count = helper::getcouponcodecount($offers->offer_code);
                    @endphp
                    @if ($offers->usage_type == 1)
                        @if ($count < $offers->usage_limit)
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <span class="coupons-label">{{ $offers->offer_code }}</span>
                                            @if (request()->is('checkout'))
                                                <p class="fw-500 cursor-pointer copy_coupon_code mb-0"
                                                   data-bs-dismiss="offcanvas"
                                                   onclick="getoffercode('{{ $offers->offer_code }}')">
                                                    {{ trans('labels.copy_code') }}
                                                </p>
                                            @endif
                                        </div>
                                        <h5 class="pt-3 mb-0 offer-text">{{ $offers->offer_name }}</h5>
                                        <p class="text-muted fw-400 fs-8 pt-2 mb-0">{{ $offers->description }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span class="coupons-label">{{ $offers->offer_code }}</span>
                                        @if (request()->is('checkout'))
                                            <p class="fw-500 cursor-pointer copy_coupon_code mb-0"
                                               data-bs-dismiss="offcanvas"
                                               onclick="getoffercode('{{ $offers->offer_code }}')">
                                                {{ trans('labels.copy_code') }}
                                            </p>
                                        @endif
                                    </div>
                                    <h5 class="pt-3 mb-0 offer-text">{{ $offers->offer_name }}</h5>
                                    <p class="text-muted fw-400 fs-8 pt-2 mb-0">{{ $offers->description }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- offer btn end-->

<div class="mobile_menu_footer d-lg-none">
    <div class="container">
        <ul class="d-flex justify-content-between align-items-center mb-0 gap-3">
            <li class="text-center">
                <a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active1' : '' }}">
                    <i class="fa-light fa-house"></i>
                    <p class="mb-0">{{ trans('labels.home') }}</p>
                </a>
            </li>
            <li class="text-center">
                <a href="{{ route('categories') }}" class="{{ request()->is('categories','menu') ? 'active1' : '' }}">
                    <i class="fa-light fa-utensils"></i>
                    <p class="mb-0">Étlap</p>
                </a>
            </li>
            <li class="text-center">
                <a href="{{ route('cart') }}" class="{{ request()->is('cart') ? 'active1' : '' }}">
                    <div class="position-relative">
                        <i class="fa-light fa-bag-shopping"></i>
                        <span class="qut_counter">{{ helper::get_user_cart() }}</span>
                    </div>
                    <p class="mb-0">{{ trans('labels.cart') }}</p>
                </a>
            </li>
            <li class="text-center">
                <a href="{{ Auth::user() ? route('user-favouritelist') : route('login') }}"
                   class="{{ request()->is('favouritelist') ? 'active1' : '' }}">
                    <i class="fa-light fa-heart"></i>
                    <p class="mb-0">{{ trans('labels.wishlist') }}</p>
                </a>
            </li>
            <li class="text-center">
                <a href="{{ Auth::user() ? route('user-profile') : route('login') }}"
                   class="{{ request()->is('profile') ? 'active1' : '' }}">
                    <i class="fa-light fa-user"></i>
                    <p class="mb-0">{{ trans('labels.account') }}</p>
                </a>
            </li>
        </ul>
    </div>
</div>

