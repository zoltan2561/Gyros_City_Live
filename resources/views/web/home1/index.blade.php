@extends('web.layout.default')
@section('page_title')
    | {{ trans('labels.home') }}
@endsection
@section('content')
    <!-- Slider Area Start Here -->
    @if (count($sliders) > 0)
        <section class="slider-area">
            <div id="slidercarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($sliders as $key => $sliderdata)
                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                            <img src="{{ helper::image_path($sliderdata->image) }}" class="d-block img-fluid" alt="slider">
                            <div class="carousel-caption d-flex h-100 align-items-center justify-content-center flex-column">
                                <h5 class="animate__animated animate__fadeInUp">{{ $sliderdata->title }}</h5>
                                <p class="animate__animated animate__fadeInUp">{{ $sliderdata->description }}</p>
                                @if ($sliderdata['item_info'] != '')
                                    <a href="{{ URL::to('/item-' . $sliderdata['item_info']->slug) }}"
                                        class="btn btn-primary fw-500 px-4 py-2 animate__animated animate__fadeInUp">{{ trans('labels.explore') }}
                                        <i class="fa-solid fa-circle-arrow-right"></i> </a>
                                @endif
                                @if ($sliderdata['category_info'] != '')
                                    <a href="{{ URL::to('/menu/?category=' . $sliderdata['category_info']->slug) }}"
                                        class="btn btn-primary fw-500 px-4 py-2 animate__animated animate__fadeInUp">{{ trans('labels.explore') }}
                                        <i class="fa-solid fa-circle-arrow-right"></i> </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev {{ count($sliders) == 1 ? 'd-none' : '' }}" type="button"
                    data-bs-target="#slidercarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next {{ count($sliders) == 1 ? 'd-none' : '' }}" type="button"
                    data-bs-target="#slidercarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            </div>
        </section>
    @endif
    <!-- Slider Area End Here -->

    <!-- Promotional topbanners Start Here -->
    @if (count($banners['topbanners']) > 0)
        <section class="theme-1-banner1 sec-padding">
            <div class="container">
                <div class="slider-small owl-carousel owl-theme">
                    @foreach ($banners['topbanners'] as $key => $bannerdata)
                        <div class="item">
                            @if ($bannerdata['item_info'] != '')
                                <a href="{{ URL::to('/item-' . $bannerdata['item_info']->slug) }}">
                                @elseif($bannerdata['category_info'] != '')
                                    <a href="{{ URL::to('/menu/?category=' . $bannerdata['category_info']->slug) }}">
                                    @else
                                        <a href="javascript:void(0);">
                            @endif
                            <img src="{{ $bannerdata['image'] }}" alt="banner" class="rounded-4">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <!-- Promotional topbanners End Here -->

    <!-- Why Choose Us Start Here -->
    @if (count($getwhychooseus) > 0)
        <section class="about sec-padding pt-0">
            <div class="container">
                <div class="row g-md-5 g-3">
                    <div class="col-lg-6 d-md-block d-none">
                        <div class="about-img h-100"><img
                                src="{{ helper::image_path(helper::appdata()->why_choose_image) }}"
                                class="w-100 h-100 object-fit-cover rounded-4" alt=""></div>
                    </div>
                    <div class="col-lg-6">
                        <div class="h-100 d-flex align-items-center py-md-4">
                            <div class="about-details">
                                <h1 class="text-uppercase">{{ helper::appdata()->why_choose_title }}</h1>
                                <p class="sub-lables text-capitalize mt-2 mb-0">
                                    {{ helper::appdata()->why_choose_subtitle }}
                                </p>
                                <p class="mb-4 line-4">{{ helper::appdata()->why_choose_description }}</p>
                                <div class="row g-4">
                                    @foreach ($getwhychooseus as $whychooseus)
                                        <div class="d-flex align-items-center">
                                            <div class="service-icon">
                                                <img src="{{ helper::image_path($whychooseus->image) }}" alt=""
                                                    class="w-100 h-100">
                                            </div>
                                            <div class="{{ session()->get('direction') == '2' ? 'pe-3' : 'ps-3' }}">
                                                <h4 class="service-name mb-1 line-1">{{ $whychooseus->title }}</h4>
                                                <p class="service-des mb-0 line-2">{{ $whychooseus->subtitle }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!-- Why Choose Us End Here -->

    <!-- Category Section Start Here -->
    @if (count(helper::get_categories()) > 0)
        <section class="category position-relative bg-section-gray sec-padding">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="row g-2 align-items-center justify-content-between mb-sm-5 mb-4">
                            <div class="col-auto">
                                <h1 class="text-uppercase fw-bold">{{ trans('labels.categories') }}</h1>
                                <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.top_categories') }}</p>
                            </div>
                            <div class="col-auto text-end align-center">
                                <a href="{{ route('categories') }}"
                                    class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}</a>
                            </div>
                        </div>
                        <div id="category" class="owl-carousel mt-2">
                            @foreach (helper::get_categories() as $categorydata)
                                <div class="category-wrapper category-item rounded-4 reveal"
                                     style="transition-delay: {{ ($loop->index % 6) * 80 }}ms">
                                    <a href="{{ URL::to('/menu/?category=' . $categorydata->slug) }}">
                                        <div class="d-flex justify-content-center">
                                            <div class="cat rounded-circle">
                                                <img src="{{ helper::image_path($categorydata->image) }}"
                                                     class="rounded-circle h-100 object-fit-cover" alt="category">
                                            </div>
                                        </div>
                                    </a>
                                    <div class="text-center pt-3 category-text">
                                        <p class="fs-6 fw-500 mb-0">{{ $categorydata->category_name }}</p>
                                        <p class="fs-7 fw-400 text-primary mb-0">
                                            {{ $categorydata->item_info->count() }} {{ trans('labels.item') }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
            <div class="burger-shape d-md-block d-none">
                <img src="https://modinatheme.com/html/foodking-html/assets/img/shape/burger-shape-2.png" alt="shape-img">
            </div>
            <div class="fry-shape d-xl-block d-none">
                <img src="https://modinatheme.com/html/foodking-html/assets/img/shape/fry-shape.png" alt="shape-img">
            </div>
        </section>
    @endif
    <!-- Category Section End Here -->

    <!-- topitemlist dishes Section Start Here -->
    @if (count($topitemlist) > 0)
        <section class="menu sec-padding position-relative">
            <div class="container">
                <div class="row g-3 align-items-center justify-content-between mb-sm-5 mb-4">
                    <div class="col-auto menu-heading">
                        <h1 class="text-uppercase">{{ trans('labels.trending') }}</h1>
                        <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.top_trending') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ URL::to('/view-all?type=topitems') }}"
                            class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}</a>
                    </div>
                </div>
                <div class="row g-4">
                    @foreach ($topitemlist as $itemdata)
                        @include('web.home1.itemview')
                    @endforeach
                </div>
            </div>
            <div class="tomato-shape-1 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/tomato-shape.png') }}"
                    alt="shape-img">
            </div>
            <div class="chili-shape-1 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/chili-shape.png') }}"
                    alt="shape-img">
            </div>
        </section>
    @endif
    <!-- topitemlist dishes Section End Here -->

    <!-- Promotional bannersection1 Start Here -->
    @if (count($banners['bannersection1']) > 0)
        <section class="banner2">
            <div id="banner1" class="carousel slide carousel-fade" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($banners['bannersection1'] as $key => $bannerdata)
                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                            @if ($bannerdata['item_info'] != '')
                                <a href="{{ URL::to('/item-' . $bannerdata['item_info']->slug) }}">
                                @elseif($bannerdata['category_info'] != '')
                                    <a href=" {{ URL::to('/menu/?category=' . $bannerdata['category_info']->slug) }} ">
                                    @else
                                        <a href="javascript:void(0)">
                            @endif
                            <img src="{{ $bannerdata['image'] }}" height="400" alt="banner2">
                            </a>
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev {{ count($banners['bannersection1']) == 1 ? 'd-none' : '' }}"
                    type="button" data-bs-target="#banner1" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ trans('labels.previous') }}</span>
                </button>
                <button class="carousel-control-next {{ count($banners['bannersection1']) == 1 ? 'd-none' : '' }}"
                    type="button" data-bs-target="#banner1" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ trans('labels.next') }}</span>
                </button>
            </div>
        </section>
    @endif
    <!-- Promotional bannersection1 End Here -->

    <!-- Table Resrvation kikpacoslva 10-re irva-->
    @if (helper::appdata()->online_table_booking == 1)
        <section class="table-booking sec-padding pb-0">
            <div class="container">
                <div class="row g-0 align-items-center bg-section-gray rounded-5">
                    <div class="reservation-content col-lg-6 p-sm-5 p-4">
                        <h1 class="text-uppercase">{{ trans('labels.book_table') }}</h1>
                        <p class="sub-lables mb-4">{{ trans('labels.make_reservation') }}</p>
                        <div>
                            <form class="rounded-5" action="{{ URL::to('reservation/store') }}" method="POST">
                                @csrf
                                <div class="row g-md-2 g-3 mb-3">
                                    <div class="col-xl-4 col-md-12 form-group">
                                        <label for="reservation_name"
                                            class="form-label fs-7 mb-1">{{ trans('labels.full_name') }}
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input class="form-control" type="text" name="name"
                                            value="{{ old('name') }}" id="reservation_name"
                                            placeholder="{{ trans('labels.full_name') }}" required>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-group">
                                            <label for="reservation_email"
                                                class="form-label fs-7 mb-1">{{ trans('labels.email') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control" type="email" name="email"
                                                value="{{ old('email') }}" id="reservation_email"
                                                placeholder="{{ trans('labels.email') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="form-group">
                                            <label for="reservation_mobile"
                                                class="form-label fs-7 mb-1">{{ trans('labels.mobile') }}
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input class="form-control" type="text" name="mobile"
                                                value="{{ old('mobile') }}" id="reservation_mobile"
                                                placeholder="{{ trans('labels.mobile') }}" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <div class="row g-md-2 g-3 mb-3">
                                            <div class="col-xl-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="reservation_date"
                                                        class="form-label fs-7 mb-1">{{ trans('labels.date') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="form-control" type="date" name="date"
                                                        min="<?php echo date('Y-m-d'); ?>" value="{{ old('date') }}"
                                                        id="reservation_date" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="reservation_time"
                                                        class="form-label fs-7 mb-1">{{ trans('labels.time') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="form-control" type="time" name="time"
                                                        value="{{ old('time') }}" id="reservation_time" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row g-md-2 g-3 mb-3">
                                            <div class="col-xl-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="reservation_guest"
                                                        class="form-label fs-7 mb-1">{{ trans('labels.number_guest') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="form-control" type="text" name="guests"
                                                        value="{{ old('guests') }}" id="reservation_guest"
                                                        placeholder="{{ trans('labels.number_guest') }}" required>
                                                </div>
                                            </div>
                                            <div class="col-xl-6 col-md-6">
                                                <div class="form-group">
                                                    <label for="reservation_type"
                                                        class="form-label fs-7 mb-1">{{ trans('labels.reservation_type') }}
                                                        <span class="text-danger">*</span>
                                                    </label>
                                                    <input class="form-control" type="text" name="reservation_type"
                                                        value="{{ old('reservation_type') }}" id="reservation_type"
                                                        placeholder="{{ trans('labels.reservation_type') }}" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="special_request"
                                                class="form-label fs-7 mb-1">{{ trans('labels.special_request') }}</label>
                                            <textarea class="form-control" name="special_request" id="special_request"
                                                placeholder="{{ trans('labels.special_request_o') }}" rows="3">{{ old('special_request') }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-center mt-3">
                                        <button type="submit"
                                            class="btn px-md-5 py-md-3 btn-primary float-end">{{ trans('labels.submit') }}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6 d-lg-block d-none table-booking-1 p-0">
                        <img src="{{ helper::image_path(@helper::appdata()->booknow_bg_image) }}"
                            class="w-100 object-fit-cover rounded-5" alt="table booking">
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!-- Table Resrvation Section End Here -->


    <!-- todayspecial Dishes Section Start Here -->
    @if (count($todayspecial) > 0)
        <section class="menu-special sec-padding position-relative">
            <div class="container">
                <div class="row g-3 align-items-center justify-content-between mb-sm-5 mb-4">
                    <div class="col-auto menu-heading">
                        <h1 class="text-uppercase">{{ trans('labels.todays_special') }}</h1>
                        <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.top_special') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ URL::to('/view-all?type=todayspecial') }}"
                            class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}</a>
                    </div>
                </div>
                <div class="row g-4">
                    @foreach ($todayspecial as $itemdata)
                        @include('web.home1.todayitemview')
                    @endforeach
                </div>
            </div>
            <div class="tomato-shape-2 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/tomato-shape.png') }}"
                    alt="shape-img">
            </div>
            <div class="chili-shape-2 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/chili-shape.png') }}"
                    alt="shape-img">
            </div>
        </section>
    @endif
    <!-- todayspecial Dishes Section End Here -->

    <!-- Promotional bannersection2 Start Here -->
    @if (count($banners['bannersection2']) > 0)
        <section class="banner1">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div id="bannersection2" class="owl-carousel">
                            @foreach ($banners['bannersection2'] as $bannerdata)
                                <div class="post-slide">
                                    <div class="post-img">
                                        @if ($bannerdata['item_info'] != '')
                                            <a href="{{ URL::to('/item-' . $bannerdata['item_info']->slug) }}">
                                            @elseif($bannerdata['category_info'] != '')
                                                <a
                                                    href="{{ URL::to('/menu/?category=' . $bannerdata['category_info']->slug) }}">
                                                @else
                                                    <a href="javascript:void(0);">
                                        @endif
                                        <img src="{{ $bannerdata['image'] }}" alt="banner">
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!-- Promotional bannersection2 End Here -->


    <!-- App Download Section Start Here -->

    <!-- App Download Section End Here -->

    <!-- recommended Section Start Here -->
    @if (count($recommended) > 0)
        <section class="menu sec-padding position-relative">
            <div class="container">
                <div class="row g-3 align-items-center justify-content-between mb-sm-5 mb-4">
                    <div class="col-auto menu-heading">
                        <h1 class="text-uppercase">{{ trans('labels.recommended') }}</h1>
                        <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.top_recommended') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ URL::to('/view-all?type=recommended') }}"
                            class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}</a>
                    </div>
                </div>
                <div class="row g-4">
                    @foreach ($recommended as $itemdata)
                        @include('web.home1.recommendeditemview')
                    @endforeach
                </div>
            </div>
            <div class="tomato-shape-3 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/tomato-shape.png') }}"
                    alt="shape-img">
            </div>
            <div class="chili-shape-3 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/chili-shape.png') }}"
                    alt="shape-img">
            </div>
        </section>
    @endif
    <!-- recommended Section End Here -->

    <!-- Promotional bannersection3 Start Here -->
    @if (count($banners['bannersection3']) > 0)
        <section class="banner2">
            <div id="banner3" class="carousel slide carousel-fade" data-bs-ride="carousel">
                <div class="carousel-inner">
                    @foreach ($banners['bannersection3'] as $key => $bannerdata)
                        <div class="carousel-item {{ $key == 0 ? 'active' : '' }}">
                            @if ($bannerdata['item_info'] != '')
                                <a href="{{ URL::to('/item-' . $bannerdata['item_info']->slug) }}">
                                @elseif($bannerdata['category_info'] != '')
                                    <a href=" {{ URL::to('/menu/?category=' . $bannerdata['category_info']->slug) }} ">
                                    @else
                                        <a href="javascript:void(0)">
                            @endif
                            <img src="{{ $bannerdata['image'] }}" height="400" alt="banner3">
                            </a>
                        </div>
                    @endforeach
                </div>
                <button class="carousel-control-prev {{ count($banners['bannersection3']) == 1 ? 'd-none' : '' }}"
                    type="button" data-bs-target="#banner3" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ trans('labels.previous') }}</span>
                </button>
                <button class="carousel-control-next {{ count($banners['bannersection3']) == 1 ? 'd-none' : '' }}"
                    type="button" data-bs-target="#banner3" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">{{ trans('labels.next') }}</span>
                </button>
            </div>
        </section>
    @endif
    <!-- Promotional bannersection3 End Here -->

    <!-- Store Reviews Section Start Here -->
    @if (helper::reviews_enabled('store_review'))
        @if (count($storereviews) >= 0)
            <section class="testimonial position-relative bg-section-gray sec-padding">
                <div class="container">
                    <div class="row align-items-center justify-content-center ">
                        <div class="mb-sm-5 mb-4">
                            <h1 class="text-uppercase">{{ trans('labels.reviews') }}</h1>
                            <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.store_reviews') }}</p>
                        </div>
                        <div class="testimonial-1 owl-carousel owl-theme">
                            @foreach ($storereviews as $key => $storereview)
                                <div class="item box p-sm-4 p-2">
                                    <div class="row gx-md-5 gx-0 gy-md-0 gy-3">
                                        <div class="col-md-12 testimonial-dec rounded-4">
                                            <img src="{{ helper::image_path($storereview->image) }}"
                                                class="testimonial-img rounded-4" alt="">
                                            <h4 class="text-capitalize fs-6 fw-600 mb-0 mt-3">
                                                {{ $storereview->name }}</h4>
                                            <p class="mt-2 text-muted">{{ Str::limit($storereview->comment, 100) }}</p>
                                            <div class="review-star fs-7 fw-500 mt-3">
                                                @if ($storereview->ratting == 1)
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                @elseif ($storereview->ratting == 2)
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                @elseif ($storereview->ratting == 3)
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                @elseif ($storereview->ratting == 4)
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-regular fa-star fs-6"></i>
                                                @elseif ($storereview->ratting == 5)
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                    <i class="fa-solid fa-star fs-6"></i>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="burger-shape-2 d-xl-block d-none">
                    <img src="https://modinatheme.com/html/foodking-html/assets/img/shape/burger-shape-3.png"
                        alt="burger-shape">
                </div>
                <div class="pizza-shape d-md-block d-none">
                    <img src="https://modinatheme.com/html/foodking-html/assets/img/shape/pizzashape.png"
                        alt="burger-shape">
                </div>
            </section>
        @endif
    @endif
    <!-- Store Reviews Section End Here -->

    <!-- Team Start Here -->
    @if (count($getteams) > 0)
        <div class="team sec-padding ">
            <div class="container">
                <div class="row g-2 align-items-center justify-content-between mb-sm-5 mb-4">
                    <div class="gallery-heading col-auto menu-heading">
                        <h1 class="text-uppercase">{{ trans('labels.team') }}</h1>
                        <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.our_team') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ URL::to('ourteam') }}"
                            class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}</a>
                    </div>
                </div>
                <div class="row g-sm-4 g-3">
                    @foreach ($getteams as $teamdata)
                        <div class="col-md-4 col-6">
                            <div class="team-card rounded-4 overflow-hidden">
                                <div class="member-img overflow-hidden position-relative">
                                    <img src="{{ helper::image_path($teamdata->image) }}"
                                        class="img-circle img-responsive" />
                                    <div class="team-social">
                                        @if ($teamdata->fb != '')
                                            <div class="icons">
                                                <a href="{{ $teamdata->fb }}" target="_blank">
                                                    <i class="fa-brands fa-facebook-f text-white"></i>
                                                </a>
                                            </div>
                                        @endif
                                        @if ($teamdata->twitter != '')
                                            <div class="icons">
                                                <a href="{{ $teamdata->twitter }}" target="_blank">
                                                    <i class="fa-brands fa-twitter text-white"></i>
                                                </a>
                                            </div>
                                        @endif
                                        @if ($teamdata->insta != '')
                                            <div class="icons">
                                                <a href="{{ $teamdata->insta }}" target="_blank">
                                                    <i class="fa-brands fa-instagram text-white"></i>
                                                </a>
                                            </div>
                                        @endif
                                        @if ($teamdata->youtube != '')
                                            <div class="icons">
                                                <a href="{{ $teamdata->youtube }}" target="_blank">
                                                    <i class="fa-brands fa-youtube text-white"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="member-details">
                                    <span class="member-name">{{ $teamdata->name }}</span>
                                    <p class="mb-0 fs-7 fw-500">{{ $teamdata->designation }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    <!-- Team End Here -->

    <!-- Top Deal Section Start Here -->
    @if (count($topdealsproduct) > 0 && $topdeals != null)
        <section class="theme-1-top-deal menu-special position-relative sec-padding bg-primary-rgb">
            <div class="container">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="p-4 rounded-4 overflow-hidden">
                            <div class="deals-heading mb-md-0 mb-3 text-center">
                                <h1 class="text-uppercase">{{ trans('labels.deals') }}</h1>
                                <p class="sub-lables text-capitalize mb-0 mt-md-2">
                                    {{ trans('labels.top_deals') }}
                                </p>
                            </div>
                            <div class="countdown d-flex justify-content-center gap-2 mt-3" id="countdown"></div>
                        </div>
                    </div>
                    @foreach ($topdealsproduct as $itemdata)
                        @include('web.home1.todayitemview')
                    @endforeach
                </div>
                <div class="col-lg-auto text-center mt-5">
                    <a href="{{ URL::to('/view-all?type=topdeals') }}"
                        class="btn btn-sm btn-outline-primary px-4 py-2 rounded-3">{{ trans('labels.view_all') }}
                        <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="tomato-shape-3 d-md-flex d-none">
                <img src="{{ url('storage/app/public/web-assets/images/theme-bg-image/tomato-shape.png') }}"
                    alt="shape-img">
            </div>
        </section>
    @endif
    <!-- Top Deal Section End Here -->

    <!-- FAQs Start Here -->
    @if (count($getfaqs) > 0)
        <div class="faqs sec-padding">
            <div class="container">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="row align-items-center mb-sm-5 mb-4">
                            <div class="gallery-heading col-auto menu-heading">
                                <h1 class="text-uppercase">{{ trans('labels.faq') }}</h1>
                                <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.faq') }}</p>
                            </div>
                        </div>
                        <div class="accordion" id="accordionfaq">
                            @foreach ($getfaqs as $key => $faqdata)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faq{{ $key }}">
                                        <button
                                            class="accordion-button {{ $key == 0 ? '' : 'collapsed' }} {{ session()->get('direction') == '2' ? 'rtl' : '' }} "
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#faqs{{ $key }}" aria-expanded="true"
                                            aria-controls="faqs{{ $key }}">
                                            {{ $faqdata->title }}
                                        </button>
                                    </h2>
                                    <div id="faqs{{ $key }}"
                                        class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}"
                                        aria-labelledby="faq{{ $key }}" data-bs-parent="#accordionfaq">
                                        <div class="accordion-body">
                                            {{ $faqdata->description }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-lg-6 col-12 d-md-block d-none">
                        <img src="{{ url('storage/app/public/prof.jpg') }}"
                            class="w-100 object-fit-cover rounded-4" alt="">
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- FAQs End Here -->


    <!-- Blog Section Start Here -->

    <!-- Blog Section End Here -->

    <!-- slider-gallery start Here -->
    @if (count($getgalleries) > 0)
        <section class="gallery pb-5 position-relative">
            <div class="container">
                <div class="row align-items-center mb-sm-5 mb-4">
                    <div class="gallery-heading col-auto menu-heading">
                        <h1 class="text-uppercase">{{ trans('labels.gallery') }}</h1>
                        <p class="sub-lables text-capitalize mt-2 mb-0">{{ trans('labels.our_gallery') }}</p>
                    </div>
                </div>
            </div>
            <div class="gallery-slider owl-carousel owl-theme">
                @foreach ($getgalleries as $image)
                    <div class="item" data-src="{{ $image->image_url }}" data-fancybox="gallery"
                        data-thumb="{{ $image->image_url }}">
                        <img src="{{ helper::image_path($image->image) }}" class="rounded-4" alt="">
                    </div>
                @endforeach
            </div>
        </section>
    @endif
    <!-- slider-gallery end Here -->

    @include('web.subscribeform')
@endsection
@section('scripts')
    <!-- JS For Promotional Banner Section 1 -->
    <script>
        $(document).ready(function() {
            $("#news-slider ").owlCarousel({
                rtl: @if (session()->get('direction') == '2')
                    true
                @else
                    false
                @endif ,
                loop: true,
                responsiveClass: true,
                responsive: {
                    0: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    400: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    600: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    800: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    1000: {
                        items: 3,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    1200: {
                        items: 3,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    }
                }
            });
        });
    </script>
    <!-- JS For Category Section -->



    <script>
        $(document).ready(function() {
            $("#category").owlCarousel({
                rtl: @if (session()->get('direction') == '2')
                    true
                @else
                    false
                @endif ,
                loop: true,
                responsiveClass: true,
                responsive: {
                    0: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                    },
                    426: {
                        items: 3,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 15,
                    },
                    600: {
                        items: 4,
                        nav: false,
                        dots: false,
                        margin: 15,
                    },
                    800: {
                        items: 4,
                        nav: false,
                        dots: false,
                        margin: 10,
                    },
                    1025: {
                        items: 5,
                        dots: false,
                        nav: false,
                        loop: false,
                        arrows: true,
                        margin: 20,
                    },
                }
            });
        });
    </script>
    <!-- JS For Promotional Banner Section 3 -->
    <script>
        $(document).ready(function() {
            $("#bannersection2").owlCarousel({
                rtl: @if (session()->get('direction') == '2')
                    true
                @else
                    false
                @endif ,
                loop: true,
                responsiveClass: true,
                responsive: {
                    0: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    400: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    600: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    800: {
                        items: 2,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    1000: {
                        items: 3,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    },
                    1200: {
                        items: 4,
                        nav: false,
                        dots: false,
                        arrow: true,
                        margin: 10,
                        loop: false,
                        // rewind: true
                    }
                }
            });
            $('.testimonial-1').owlCarousel({
                rtl: @if (session()->get('direction') == '2')
                    true
                @else
                    false
                @endif ,
                loop: true,
                margin: 20,
                nav: false,
                dots: true,
                autoplay: true,
                autoplayTimeout: 4000,
                responsive: {
                    0: {
                        items: 1
                    },
                    500: {
                        items: 1
                    },
                    1000: {
                        items: 2
                    },
                    1200: {
                        items: 2
                    },
                }
            });
            $('.slider-small').owlCarousel({
                rtl: @if (session()->get('direction') == '2')
                    true
                @else
                    false
                @endif ,
                loop: true,
                margin: 20,
                responsiveClass: true,
                nav: false,
                dots: false,
                autoplay: true,
                autoplaySpeed: 2000,
                autoplayTimeout: 5000,
                responsive: {
                    0: {
                        items: 2,
                        margin: 10

                    },
                    500: {
                        items: 2,
                        margin: 15

                    },
                    600: {
                        items: 2,
                        margin: 20

                    },
                    1000: {
                        items: 2,
                        margin: 20

                    }
                }
            });
        });
    </script>
    <!-- slider-gallery -->
    <script>
        $('.gallery-slider').owlCarousel({
            rtl: @if (session()->get('direction') == '2')
                true
            @else
                false
            @endif ,
            loop: true,
            margin: 10,
            responsiveClass: true,
            nav: false,
            dots: false,
            center: true,
            autoplay: true,
            slideTransition: 'linear',
            autoplaySpeed: 3000,
            smartSpeed: 3000,
            autoplayTimeout: 3000,
            responsive: {
                0: {
                    items: 1,
                },
                425: {
                    items: 2,
                },
                600: {
                    items: 3,
                },
                1000: {
                    items: 5,
                }
            }
        });
    </script>
@endsection


<script>
    (function () {
        // Ha a böngésző nem támogatja, fallback: minden azonnal látszik
        if (!('IntersectionObserver' in window)) {
            document.querySelectorAll('.reveal').forEach(el => el.classList.add('in-view'));
            return;
        }

        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    // Ha egyszer megjelent, nem kell többször figyelni
                    obs.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '0px 0px -10% 0px', // kicsit előbb triggerel
            threshold: 0.1
        });

        // Figyeljük az összes reveal elemet
        document.querySelectorAll('.reveal').forEach(el => io.observe(el));
    })();
</script>
