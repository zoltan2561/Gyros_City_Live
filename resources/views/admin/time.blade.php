@extends('admin.theme.default')
<link rel="stylesheet"
    href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/timepicker/jquery.timepicker.min.css') }}">
@section('styles')
    <style>
        @media (min-width: 992px) {
            .hours-day-row, .hours-day-header { display: grid; grid-template-columns: 1.1fr repeat(5, minmax(0, 1fr)) .8fr; gap: 10px; margin-inline: 0; }
            .hours-day-row > *, .hours-day-header > * { width: 100%; padding-inline: 0; min-width: 0; }
        }
    </style>
@endsection
@section('content')
    @include('admin.breadcrumb')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0">
                    <div class="card-body">
                        <div class="basic-form">
                            <form action="{{ URL::to('admin/time/store') }}" method="post">
                                @csrf
                                <div class="alert alert-info">
                                    Az online rendeléseket a napi nyitástól az „Online rendelés eddig” időpontig fogadjuk.
                                    A szünet és a zárt napok erre is érvényesek. A felső BE/KI kapcsolóval bármikor leállítható a rendelésfogadás.
                                </div>
                                @if (!$onlineHoursReady)<p class="alert alert-warning">Az online rendelési határidő beállításához adatbázis-frissítés szükséges.</p>@endif
                                @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                                <div class="row">
                                    <div class="col-md-4 mb-lg-0">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('labels.time_interval') }}<span
                                                    class="text-danger"> *
                                                </span></label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control {{ session()->get('direction') == 2 ? 'input-group-rtl rounded-end' : '' }} numbers_only"
                                                    name="interval_time" placeholder="{{ trans('labels.time_interval') }}"
                                                    aria-describedby="button-addon2"
                                                    value="{{ $settingsdata->interval_time }}" required>
                                                <select name="interval_type" class="border border-muted {{ session()->get('direction') == 2 ? 'rounded-start' : 'rounded-end' }}" required>
                                                    <option value="1"
                                                        {{ $settingsdata->interval_type == 1 ? 'selected' : '' }}>
                                                        {{ trans('labels.minute') }}
                                                    </option>
                                                    <option value="2"
                                                        {{ $settingsdata->interval_type == 2 ? 'selected' : '' }}>
                                                        {{ trans('labels.hour') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-lg-0">
                                        <div class="form-group">
                                            <label class="form-label">{{ trans('labels.perslot_booking_limit') }}
                                                <span class="text-danger"> * </span></label>
                                            <input type="number" class="form-control" name="slot_limit"
                                                placeholder="{{ trans('labels.perslot_booking_limit') }}"
                                                aria-describedby="button-addon2"
                                                value="{{ $settingsdata->perslot_booking_limit }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label class="form-label" for="">{{ trans('labels.date_time') }}
                                        </label>
                                        <input id="ordertypedatetime-switch" type="checkbox" class="checkbox-switch"
                                            name="ordertypedatetime" value="1"
                                            {{ $settingsdata->ordertype_date_time == 1 ? 'checked' : '' }}>
                                        <label for="ordertypedatetime-switch" class="switch">
                                            <span
                                                class="{{ session()->get('direction') == 2 ? 'switch__circle-rtl' : 'switch__circle' }}"><span
                                                    class="switch__circle-inner"></span></span>
                                            <span
                                                class="switch__left {{ session()->get('direction') == 2 ? 'pe-2' : 'ps-2' }}">{{ trans('labels.off') }}</span>
                                            <span
                                                class="switch__right {{ session()->get('direction') == 2 ? 'ps-2' : 'pe-2' }}">{{ trans('labels.on') }}</span>
                                        </label>
                                    </div>

                                </div>
                                <div class="row mt-2 hours-day-header">
                                    <label class="col-md-2 col-form-label"></label>
                                    <label
                                        class="col-md-2 text-center mb-0 d-none d-lg-block d-xl-block d-xxl-block">{{ trans('labels.opening_time') }}</label>
                                    <label
                                        class="col-md-2 text-center mb-0 d-none d-lg-block d-xl-block d-xxl-block">{{ trans('labels.break_start') }}</label>
                                    <label
                                        class="col-md-2 text-center mb-0 d-none d-lg-block d-xl-block d-xxl-block">{{ trans('labels.break_end') }}</label>
                                    <label
                                        class="col-md-2 text-center mb-0 d-none d-lg-block d-xl-block d-xxl-block">{{ trans('labels.closing_time') }}</label>
                                    <label class="col-md-2 text-center mb-0 d-none d-lg-block">Online rendelés eddig</label>
                                    <label
                                        class="col-md-2 text-center mb-0 d-none d-lg-block d-xl-block d-xxl-block">{{ trans('labels.is_closed') }}</label>
                                </div>
                                @foreach ($gettime as $key => $time)
                                    <div class="row my-2 hours-day-row">
                                        <label class="col-md-2 col-form-label"> <strong class="fw-500">
                                                {{ trans('labels.' . strtolower($time->day)) }} </strong> </label>
                                        <input type="hidden" name="day[]" value="{{ strtolower($time->day) }}">
                                        @if ($time->always_close == '2')
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.opening_time') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content timepicker {{ $errors->has('open_time.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.opening_time') }}" name="open_time[]"
                                                    value="{{ $time->open_time }}" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.break_start') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content timepicker {{ $errors->has('break_start.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.break_start') }}" name="break_start[]"
                                                    value="{{ $time->break_start }}" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.break_end') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content timepicker {{ $errors->has('break_end.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.break_end') }}" name="break_end[]"
                                                    value="{{ $time->break_end }}" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.closing_time') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content timepicker {{ $errors->has('close_time.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.closing_time') }}" name="close_time[]"
                                                    value="{{ $time->close_time }}" required>
                                            </div>
                                        @else
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.opening_time') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content {{ $errors->has('open_time.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.opening_time') }}" name="open_time[]"
                                                    value="closed" readonly="" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.break_start') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content {{ $errors->has('break_start.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.break_start') }}" name="break_start[]"
                                                    value="closed" readonly="" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.break_end') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content {{ $errors->has('break_end.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.break_end') }}" name="break_end[]"
                                                    value="closed" readonly="" required>
                                            </div>
                                            <div class="form-group col-md-2 d-grid align-items-end">
                                                <label
                                                    class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.closing_time') }}</label>
                                                <input type="text"
                                                    class="form-control h-fit-content {{ $errors->has('close_time.' . $key) ? 'is-invalid' : '' }}"
                                                    placeholder="{{ trans('labels.closing_time') }}" name="close_time[]"
                                                    value="closed" readonly="" required>
                                            </div>
                                        @endif
                                        <div class="form-group col-md-2 d-grid align-items-end">
                                            <label for="online-order-until-{{ $key }}" class="d-lg-none">Online rendelés eddig</label>
                                            <input type="time" id="online-order-until-{{ $key }}" name="online_order_until[]"
                                                class="form-control {{ $errors->has('online_order_until.'.$key) ? 'is-invalid' : '' }}"
                                                aria-label="{{ trans('labels.'.strtolower($time->day)) }} – Online rendelés eddig"
                                                value="{{ old('online_order_until.'.$key, $time->online_order_until ?? '21:30') }}"
                                                @if ($onlineHoursReady) required @else disabled @endif>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label
                                                class="d-lg-none d-xl-none d-xxl-none">{{ trans('labels.is_closed') }}</label>
                                            <select
                                                class="form-select h-fit-content {{ $errors->has('always_close.' . $key) ? 'is-invalid' : '' }}"
                                                name="always_close[]" required>
                                                <option value="" selected disabled>{{ trans('labels.select') }}
                                                </option>
                                                <option value="1" @if ($time->always_close == '1') selected @endif>
                                                    {{ trans('labels.yes') }}</option>
                                                <option value="2" @if ($time->always_close == '2') selected @endif>
                                                    {{ trans('labels.no') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="form-group {{ session()->get('direction') == '2' ? 'text-start' : 'text-end' }}">
                                    <button class="btn btn-primary"
                                        @if (env('Environment') == 'sendbox') type="button" onclick="myFunction()" @else type="submit" @endif>{{ trans('labels.save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/timepicker/jquery.timepicker.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            "use strict";
            $(".timepicker").timepicker();
        });
    </script>
@endsection
