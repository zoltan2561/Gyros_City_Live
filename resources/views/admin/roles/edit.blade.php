@extends('admin.theme.default')
@section('content')
    @include('admin.breadcrumb')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card border-0">
                    <div class="card-body">
                        <div class="form-validation roles-form">
                            <form action="{{ URL::to('admin/roles/update-' . $data->id) }}" method="post">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="col-form-label" for="">{{ trans('labels.role_name') }}
                                                <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name"
                                                placeholder="{{ trans('labels.role_name') }}" value="{{ $data->name }}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        @php $modules = explode(',',$data->modules); @endphp
                                        <div class="form-group">
                                            <label class="col-form-label"
                                                for="">{{ trans('labels.system_modules') }}
                                                <span class="text-danger">*</span></label>
                                            <div class="row mb-0">
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox0">
                                                        <input type="checkbox" class="me-2" id="checkbox0"
                                                            name="modules[]" value="0"
                                                            {{ in_array('0', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.dashboard') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.dashboard') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox1">
                                                        <input type="checkbox" class="me-2" id="checkbox1"
                                                            name="modules[]" value="1"
                                                            {{ in_array('1', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.orders') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.orders') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox2">
                                                        <input type="checkbox" class="me-2" id="checkbox2"
                                                            name="modules[]" value="2"
                                                            {{ in_array('2', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.report') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.report') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox3">
                                                        <input type="checkbox" class="me-2" id="checkbox3"
                                                            name="modules[]" value="3"
                                                            {{ in_array('3', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.sliders') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.sliders') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox4">
                                                        <input type="checkbox" class="me-2" id="checkbox4"
                                                            name="modules[]" value="4"
                                                            {{ in_array('4', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.banners') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.banners') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox5">
                                                        <input type="checkbox" class="me-2" id="checkbox5"
                                                            name="modules[]" value="5"
                                                            {{ in_array('5', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.promocodes') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.promocodes') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox6">
                                                        <input type="checkbox" class="me-2" id="checkbox6"
                                                            name="modules[]" value="6"
                                                            {{ in_array('6', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.notification') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.notification') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox7">
                                                        <input type="checkbox" class="me-2" id="checkbox7"
                                                            name="modules[]" value="7"
                                                            {{ in_array('7', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.categories') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.categories') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox8">
                                                        <input type="checkbox" class="me-2" id="checkbox8"
                                                            name="modules[]" value="8"
                                                            {{ in_array('8', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.subcategories') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.subcategories') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox9">
                                                        <input type="checkbox" class="me-2" id="checkbox9"
                                                            name="modules[]" value="9"
                                                            {{ in_array('9', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.addons_group') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.addons_group') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox10">
                                                        <input type="checkbox" class="me-2" id="checkbox10"
                                                            name="modules[]" value="10"
                                                            {{ in_array('10', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.items') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.items') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox11">
                                                        <input type="checkbox" class="me-2" id="checkbox11"
                                                            name="modules[]" value="11"
                                                            {{ in_array('11', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.shippingarea') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.shippingarea') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox12">
                                                        <input type="checkbox" class="me-2" id="checkbox12"
                                                            name="modules[]" value="12"
                                                            {{ in_array('12', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.working_hours') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.working_hours') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox13">
                                                        <input type="checkbox" class="me-2" id="checkbox13"
                                                            name="modules[]" value="13"
                                                            {{ in_array('13', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.payment_methods') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.payment_methods') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox14">
                                                        <input type="checkbox" class="me-2" id="checkbox14"
                                                            name="modules[]" value="14"
                                                            {{ in_array('14', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.store_reviews') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.store_reviews') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox15">
                                                        <input type="checkbox" class="me-2" id="checkbox15"
                                                            name="modules[]" value="15"
                                                            {{ in_array('15', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.bookings') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.bookings') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox16">
                                                        <input type="checkbox" class="me-2" id="checkbox16"
                                                            name="modules[]" value="16"
                                                            {{ in_array('16', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.inquiries') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.inquiries') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox17">
                                                        <input type="checkbox" class="me-2" id="checkbox17"
                                                            name="modules[]" value="17"
                                                            {{ in_array('17', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.customers') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.customers') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox18">
                                                        <input type="checkbox" class="me-2" id="checkbox18"
                                                            name="modules[]" value="18"
                                                            {{ in_array('18', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.drivers') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.drivers') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox19">
                                                        <input type="checkbox" class="me-2" id="checkbox19"
                                                            name="modules[]" value="19"
                                                            {{ in_array('19', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.employee_role') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.employee_role') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox20">
                                                        <input type="checkbox" class="me-2" id="checkbox20"
                                                            name="modules[]" value="20"
                                                            {{ in_array('20', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.employee') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.employee') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox21">
                                                        <input type="checkbox" class="me-2" id="checkbox21"
                                                            name="modules[]" value="21"
                                                            {{ in_array('21', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.pages') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.pages') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox22">
                                                        <input type="checkbox" class="me-2" id="checkbox22"
                                                            name="modules[]" value="22"
                                                            {{ in_array('22', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.general_settings') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.general_settings') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox23">
                                                        <input type="checkbox" class="me-2" id="checkbox23"
                                                            name="modules[]" value="23"
                                                            {{ in_array('23', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.addons_manager') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.addons_manager') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox24">
                                                        <input type="checkbox" class="me-2" id="checkbox24"
                                                            name="modules[]" value="24"
                                                            {{ in_array('24', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.clear_cache') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.clear_cache') }}">
                                                    </label>
                                                </div>
                                                @if (@helper::checkaddons('pos'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox25">
                                                            <input type="checkbox" class="me-2" id="checkbox25"
                                                                name="modules[]" value="25"
                                                                {{ in_array('25', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.pos') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]" value="{{ trans('labels.pos') }}">
                                                @if (@helper::checkaddons('otp'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox26">
                                                            <input type="checkbox" class="me-2" id="checkbox26"
                                                                name="modules[]" value="26"
                                                                {{ in_array('26', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.otp_configuration') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.otp_configuration') }}">

                                                @if (@helper::checkaddons('language'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox27">
                                                            <input type="checkbox" class="me-2" id="checkbox27"
                                                                name="modules[]" value="27"
                                                                {{ in_array('27', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.language') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.language') }}">
                                                @if (@helper::checkaddons('whatsapp_message'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox28">
                                                            <input type="checkbox" class="me-2" id="checkbox28"
                                                                name="modules[]" value="28"
                                                                {{ in_array('28', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.whatsapp_settings') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.whatsapp_settings') }}">

                                                @if (@helper::checkaddons('product_review'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox29">
                                                            <input type="checkbox" class="me-2" id="checkbox29"
                                                                name="modules[]" value="29"
                                                                {{ in_array('29', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.product_reviews') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.product_reviews') }}">
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox30">
                                                        <input type="checkbox" class="me-2" id="checkbox30"
                                                            name="modules[]" value="30"
                                                            {{ in_array('30', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.tax') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.tax') }}">
                                                    </label>
                                                </div>
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox31">
                                                        <input type="checkbox" class="me-2" id="checkbox31"
                                                            name="modules[]" value="31"
                                                            {{ in_array('31', $modules) == true ? 'checked' : '' }}>
                                                        {{ trans('labels.global_extras') }}
                                                        <input type="hidden" name="title[]"
                                                            value="{{ trans('labels.global_extras') }}">
                                                    </label>
                                                </div>
                                                @if (@helper::checkaddons('custom_status'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox32">
                                                            <input type="checkbox" class="me-2" id="checkbox32"
                                                                name="modules[]" value="32"
                                                                {{ in_array('32', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.custom_status') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.custom_status') }}">
                                                @if (@helper::checkaddons('top_deals'))
                                                    <div class="col-lg-3 col-md-6 py-2">
                                                        <label class="cursor-pointer" for="checkbox33">
                                                            <input type="checkbox" class="me-2" id="checkbox33"
                                                                name="modules[]" value="33"
                                                                {{ in_array('33', $modules) == true ? 'checked' : '' }}>
                                                            {{ trans('labels.top_deals') }}
                                                        </label>
                                                    </div>
                                                @endif
                                                <input type="hidden" name="title[]"
                                                    value="{{ trans('labels.top_deals') }}">
                                                <div class="col-lg-3 col-md-6 py-2">
                                                    <label class="cursor-pointer" for="checkbox35">
                                                        <input type="checkbox" class="me-2" id="checkbox35" name="modules[]" value="35"
                                                            {{ in_array('35', old('modules', $modules)) ? 'checked' : '' }}>
                                                        {{ trans('admin_ui.signage') }}
                                                    </label>
                                                    <input type="hidden" name="title[35]" value="{{ trans('admin_ui.signage') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group {{ session()->get('direction') == '2' ? 'text-start' : 'text-end' }}">
                                    <a href="{{ URL::to('admin/roles') }}"
                                        class="btn btn-danger">{{ trans('labels.cancel') }}</a>
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
