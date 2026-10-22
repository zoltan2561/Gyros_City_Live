@extends('web.layout.default')
@section('page_title')
    | {{ trans('labels.help_contact_us') }}
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
                            aria-current="page">{{ trans('labels.help_contact_us') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- Contact us Section Start Here -->
    <section>
        <div class="contact-us">
            <div class="container">
                <div class="contact-content">
                    <div class="row">
                        <div class="col-md-6 col-sm-12 col-auto right-side">
                            <form method="POST" class="p-5" action="{{ route('createcontact') }}">
                                @csrf
                                <div class="row">
                                    <p class="fs-2 fw-600">{{ trans('labels.contactus_heading') }}</p>
                                    <span class="text-muted">{{ trans('labels.contactus_description') }}</span>
                                </div>
                                <div class="mb-3 mt-5 form-group">
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="text" class="form-control" name="fname"
                                                placeholder="{{ trans('messages.first_name') }}" required>
                                        </div>
                                        <div class="col-6">
                                            <input type="text" class="form-control" name="lname"
                                                placeholder="{{ trans('messages.last_name') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 form-group">
                                    <input type="email" class="form-control" name="email"
                                        placeholder="{{ trans('labels.email') }}" required>
                                </div>
                                <div class="mb-3 form-group">
                                    <textarea class="form-control" rows="2" name="message" placeholder="{{ trans('labels.message') }}" required></textarea>
                                </div>
                                <div class="col-12 d-inline-block">
                                    <button type="submit"
                                        class="btn px-4 py-2 btn-primary float-end">{{ trans('labels.save') }}</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 col-sm-12 col-auto p-3 left-side">
                            <div class="col-12 my-1">
                                <div class="card border-0  rounded-3 p-3 h-100 ">
                                    <h2 class="fw-bold">{{ trans('labels.get_in_touch') }}</h2>
                                </div>
                            </div>
                            <div class="col-xl-12 col-lg-6 col-sm-6 my-1">
                                <div class="card border-0  rounded-3 p-3 h-100">
                                    <h5><i class="fa-solid fa-location-dot {{ session()->get('direction') == '2' ? 'ps-2' : 'pe-2' }}"></i>{{ trans('labels.address') }}</h5>
                                    <a href="{{ @helper::appdata()->address_url }}">{{ @helper::appdata()->address }}</a>
                                </div>
                            </div>
                            <div class="row mb-4 ">
                                <div class="col-xl-6 col-lg-6 col-sm-6 my-1">
                                    <div class="card border-0  rounded-3 p-3 h-100">
                                        <h5> <i class="fa-solid fa-envelope {{ session()->get('direction') == '2' ? 'ps-2' : 'pe-2' }}"></i>{{ trans('labels.email') }}</h5>
                                        <a href="mailto:{{ @helper::appdata()->email }}"
                                            class=" text-break">{{ @helper::appdata()->email }}</a>
                                    </div>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-sm-6 my-1">
                                    <div class="card border-0  rounded-3 p-3 h-100 ">
                                        <h5> <i class="fa-solid fa-phone {{ session()->get('direction') == '2' ? 'ps-2' : 'pe-2' }}"></i>{{ trans('labels.mobile') }}</h5>
                                        <a href="tel:{{ @helper::appdata()->mobile }}"
                                            class="text-break">{{ @helper::appdata()->mobile }}</a>
                                    </div>
                                </div>
                                <div class="col-xl-12 col-lg-6 col-sm-6 my-1">
                                    <div class="card border-0 rounded-3 p-3 h-100 row">
                                        <h5><i class="fa-solid fa-clock {{ session()->get('direction') == '2' ? 'ps-2' : 'pe-2' }}"></i>{{ trans('labels.working_hours') }}</h5>
                                        <h6 class="text-muted">{{ ucfirst($timedata->day) }}
                                            <span class="cursor-pointer" data-bs-toggle="modal"
                                                data-bs-target="#modal_working_hours">
                                                <i class="fa-solid fa-circle-info fs-6 text-dark"></i>
                                            </span>
                                        </h6>
                                        @if ($timedata->always_close == 1)
                                            <span
                                                class="badge bg-danger fs-6 col-xl-4 col-12">{{ trans('labels.closing_time') }}</span>
                                        @else
                                            <p>{{ $timedata->open_time }} <b>{{ trans('labels.to') }}</b>
                                                {{ $timedata->break_start }}</p>
                                            <p>{{ $timedata->break_end }} <b>{{ trans('labels.to') }}</b>
                                                {{ $timedata->close_time }}</p>
                                            @include('web.partials.online-order-deadline', ['hours' => $timedata])
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Contact us Section End Here -->
    @include('web.subscribeform')
@endsection
