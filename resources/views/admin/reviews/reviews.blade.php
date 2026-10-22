@extends('admin.theme.default')
@section('styles')
<link rel="stylesheet" href="{{ url('public/city/admin-reviews.css') }}?v={{ filemtime(public_path('city/admin-reviews.css')) }}">
@endsection
@section('content')
<div class="container-fluid admin-reviews-page">
    <h1 class="h3">{{ trans('reviews.title') }}</h1>
    <p class="text-muted">{{ trans('reviews.manual_help') }}</p>
    @if ((int)Auth::user()->type === 1)
        @include('admin.reviews.review_setting')
    @endif
    @if ((int)($getsettings->reviews_enabled ?? 2) !== 1)
        <div class="alert alert-info">{{ trans('reviews.disabled') }}</div>
    @endif
    <div class="card border-0"><div class="card-body">
        <form method="get" action="{{ url('admin/reviews') }}" class="row g-3 align-items-end mb-4">
            <div class="col-md-4">
                <label for="review-kind" class="form-label">{{ trans('reviews.all_kinds') }}</label>
                <select name="kind" id="review-kind" class="form-select">
                    <option value="">{{ trans('reviews.all_kinds') }}</option>
                    <option value="restaurant" @selected(request('kind') === 'restaurant')>{{ trans('reviews.restaurant') }}</option>
                    <option value="product" @selected(request('kind') === 'product')>{{ trans('reviews.product') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="review-status" class="form-label">{{ trans('labels.status') }}</label>
                <select name="status" id="review-status" class="form-select">
                    <option value="">{{ trans('reviews.all_status') }}</option>
                    <option value="2" @selected(request('status') == '2')>{{ trans('reviews.pending') }}</option>
                    <option value="1" @selected(request('status') == '1')>{{ trans('reviews.published') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="review-item" class="form-label">{{ trans('labels.item_name') }}</label>
                <select name="item_id" id="review-item" class="form-select">
                    <option value="">{{ trans('reviews.all_products') }}</option>
                    @foreach ($getproduct as $product)
                        <option value="{{ $product->id }}" @selected((string)$sorter === (string)$product->id)>{{ $product->item_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100" type="submit">{{ trans('reviews.filter') }}</button></div>
        </form>
        <div class="table-responsive" id="table-display">@include('admin.reviews.table')</div>
    </div></div>
</div>
@endsection
