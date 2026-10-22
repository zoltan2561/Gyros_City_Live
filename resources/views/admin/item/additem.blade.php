@extends('admin.theme.default')
@section('styles')
    <link rel="stylesheet"
        href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/bootstrap/bootstrap-select.v1.14.0-beta2.min.css') }}">
    <link rel="stylesheet" href="{{ url('public/city/admin-item-form.css') }}?v={{ filemtime(public_path('city/admin-item-form.css')) }}">
@endsection
@section('content')
    <div class="container-fluid item-editor">
        <div class="row">
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body">
                        <div class="item-editor-heading">
                            <div>
                                <a class="item-editor-back" href="{{ url('admin/item') }}">← Vissza a termékekhez</a>
                                <h1>Új termék</h1>
                                <p>Add meg a termék adatait. A csillaggal jelölt mezők kitöltése kötelező.</p>
                            </div>
                        </div>
                        <div id="privacy-policy-three" class="privacy-policy">
                            <form method="post" action="{{ URL::to('admin/item/store') }}" name="about" id="about"
                                enctype="multipart/form-data">
                                @csrf
                                <h2 class="item-editor-section">Alapadatok</h2>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="cat_id" class="col-form-label">{{ trans('labels.category') }}
                                                <span class="text-danger">*</span> </label>
                                            <select name="cat_id" class="form-select" id="cat_id" required
                                                data-url="{{ URL::to('admin/item/subcategories') }}">
                                                <option value="" selected>{{ trans('labels.select') }}
                                                </option>
                                                @foreach ($getcategory as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ old('cat_id') == $category->id ? 'selected' : '' }}
                                                        data-id="{{ $category->id }}">{{ $category->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="emsg text-danger"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subcat_id"
                                                class="col-form-label">{{ trans('labels.subcategory') }}</label>
                                            <select name="subcat_id" class="form-select" id="subcat_id">
                                                <option value="" selected>{{ trans('labels.select') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.name') }}
                                                <span class="text-danger">*</span> </label>
                                            <input type="text" class="form-control" name="item_name"
                                                value="{{ old('item_name') }}" placeholder="{{ trans('labels.name') }}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.video_url') }}</label>
                                            <input type="text" class="form-control" name="video_url"
                                                value="{{ old('video_url') }}"
                                                placeholder="{{ trans('labels.video_url') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.addons_group') }}</label>
                                            <select class="form-control selectpicker" name="addongroup_id[]" multiple
                                                data-none-selected-text="Válassz kiegészítőcsoportot" data-none-results-text="Nincs találat: {0}"
                                                data-live-search="true">
                                                @foreach ($getaddongroup as $key => $addongroup)
                                                    @php
                                                        $availableAddons = collect($getaddon)->where(
                                                            'addongroup_id',
                                                            $addongroup->id,
                                                        );
                                                    @endphp
                                                    @if ($availableAddons->isNotEmpty())
                                                        <option value="{{ $addongroup->id }}"
                                                            {{ !empty(old('addongroup_id')) && in_array($addongroup->id, old('addongroup_id')) ? 'selected' : '' }}>
                                                            {{ $addongroup->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        @include('admin.item.type-options')
                                    </div>
                                </div>
                                <h2 class="item-editor-section">Extrák</h2>
                                <div class="row">
                                    <div class="col-md-12 d-flex flex-wrap justify-content-between align-items-center">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.item_has_extras') }}</label>
                                            <div class="col-md-12">
                                                <div class="form-check-inline">
                                                    <input class="form-check-input me-0 has_extras" type="radio"
                                                        name="has_extras" id="extras_no" value="2" checked
                                                        @if (old('has_extras') == 2) checked @endif>
                                                    <label class="form-check-label"
                                                        for="extras_no">{{ trans('labels.no') }}</label>
                                                </div>
                                                <div class="form-check-inline">
                                                    <input class="form-check-input me-0 has_extras" type="radio"
                                                        name="has_extras" id="extras_yes" value="1"
                                                        @if (old('has_extras') == 1) checked @endif>
                                                    <label class="form-check-label"
                                                        for="extras_yes">{{ trans('labels.yes') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="">
                                            @if (count($globalextras) > 0)
                                                <button class="btn btn-primary align-items-end  mb-sm-0 mb-2" type="button"
                                                    id="globalextra"
                                                    onclick="global_extras('{{ URL::to('admin/getextras') }}','{{ trans('labels.name') }}','{{ trans('labels.price') }}')">
                                                    <i class="fa-sharp fa-solid fa-plus"></i>
                                                    {{ trans('labels.add_global_extras') }}</button>
                                            @endif
                                            <button class="btn btn-secondary px-3 mb-sm-0 mb-2" type="button" id="add_extra"
                                                onclick="extras_fields('{{ trans('labels.name') }}','{{ trans('labels.price') }}')">
                                                <i class="fa-sharp fa-solid fa-plus"></i> </button>
                                        </div>
                                    </div>
                                    <div id="extras">
                                        @if (!empty($globalextras) && $globalextras->count() > 0)
                                            <div id="global-extras"></div>
                                        @endif
                                        <div id="more_extras_fields" class="row"></div>
                                    </div>
                                </div>
                                <h2 class="item-editor-section">Árak és elkészítés</h2>
                                <div class="row">
                                    <div class="col-sm-6 col-md-6">
                                        <div class="form-group">
                                            <label for="price"
                                                class="col-form-label">{{ trans('labels.product_price') }} <span
                                                    class="text-danger">*</span> </label>
                                            <input type="text" class="form-control numbers_only" name="price"
                                                id="price" value="{{ old('price') }}"
                                                placeholder="{{ trans('labels.product_price') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-6">
                                        <div class="form-group">
                                            <label for="original_price"
                                                class="col-form-label">{{ trans('labels.original_price') }}</label>
                                            <input type="text" class="form-control numbers_only" name="original_price"
                                                id="original_price" value="0"
                                                placeholder="{{ trans('labels.original_price') }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.image') }}
                                                <span class="text-danger">*</span> </label>
                                            <input type="file" class="form-control" name="image[]" id="image"
                                                accept="image/*" multiple required>
                                        </div>
                                        <div class="gallery"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="col-form-label">{{ trans('labels.preparation_time') }}
                                                <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="preparation_time"
                                                placeholder="{{ trans('labels.preparation_time') }}"
                                                value="{{ old('preparation_time') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="tax"
                                                class="col-form-label">{{ trans('labels.tax') }}</label>
                                            <select class="form-control selectpicker" name="tax[]" multiple
                                                data-none-selected-text="Nincs kiválasztva" data-none-results-text="Nincs találat: {0}"
                                                data-live-search="true">
                                                @foreach ($gettax as $key => $tax)
                                                    <option value="{{ $tax->id }}"
                                                        {{ !empty(old('tax')) && in_array($tax->id, old('tax')) ? 'selected' : '' }}>
                                                        {{ $tax->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h2 class="item-editor-section">Leírás és allergének</h2>
                                        <div class="form-group">
                                            <label for="description"
                                                class="col-form-label">{{ trans('labels.description') }}</label>
                                            <textarea class="form-control" rows="5" name="description" id="description"
                                                placeholder="{{ trans('labels.description') }}"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="allergens"
                                                class="col-form-label">{{ trans('labels.allergens') }}</label>
                                            <textarea class="form-control" rows="5" name="allergens" id="allergens"
                                                placeholder="{{ trans('labels.allergens') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="item-editor-actions">
                                    <p>A termék a Mentés gombbal jön létre.</p>
                                    <div class="item-editor-action-buttons">
                                    <a href="{{ URL::to('admin/item') }}"
                                        class="btn btn-outline-secondary">{{ trans('labels.cancel') }}</a>
                                    <button class="btn btn-primary"
                                        @if (env('Environment') == 'sendbox') type="button"
                                    onclick="myFunction()" @else type="submit" @endif>{{ trans('labels.save') }}</button>
                                    </div>
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
    <script>
        var placehodername = "{{ trans('labels.name') }}";
        var placeholderprice = "{{ trans('labels.price') }}";
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/ckeditor/4.12.1/ckeditor.js"></script>
    <script type="text/javascript">
        CKEDITOR.replace('allergens');
    </script>
    <script
        src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/bootstrap/bootstrap-select.v1.14.0-beta2.min.js') }}">
    </script>
    <script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/custom/additem.js') }}"></script>
@endsection
