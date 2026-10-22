@extends('web.layout.default')
@section('content')
    <div class="breadcrumb-sec">
        <div class="container">
            <nav class="text-dark breadcrumb-divider" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="text-dark fw-600" href="{{ url('/') }}">{{ trans('labels.home') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ trans('reviews.restaurant') }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <section class="testimonials-wrapper-1 py-5">
        <div class="container">
            <h1 class="mb-3">{{ trans('reviews.restaurant') }}</h1>
            @if (!helper::reviews_enabled('store_review'))
                <p class="alert alert-info" role="status">{{ trans('reviews.disabled') }}</p>
            @else
                <p class="text-muted">{{ trans('reviews.intro') }}</p>
                <div class="row mt-4 mb-3">
                    @forelse ($testimonials as $review)
                        <div class="col-lg-4 col-md-6 col-sm-12 d-flex mb-3">
                            <article class="review w-100">
                                <div class="d-flex text-start p-2">
                                    @if ($review->user_info?->profile_image)
                                        <img src="{{ $review->user_info->profile_image }}" class="img-circle img-responsive" alt="">
                                    @endif
                                    <div class="mx-2">
                                        <h2 class="h5">{{ $review->user_info?->name ?? trans('reviews.unknown_author') }}</h2>
                                        <div class="review-star" aria-label="{{ trans('reviews.stars', ['count' => $review->ratting]) }}">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <i class="{{ $star <= $review->ratting ? 'fa-solid' : 'fa-regular' }} fa-star" aria-hidden="true"></i>
                                            @endfor
                                        </div>
                                        <p class="text-muted small mb-0">{{ helper::date_format($review->created_at) }}</p>
                                    </div>
                                </div>
                                <p class="gray">{{ $review->comment }}</p>
                            </article>
                        </div>
                    @empty
                        @include('web.nodata', ['emptyTitle' => trans('reviews.empty')])
                    @endforelse
                </div>
                <div class="mb-4 d-flex justify-content-center">{{ $testimonials->links() }}</div>
                @if (Auth::user() && (int) Auth::user()->type === 2)
                    @if (helper::check_review_exist(Auth::id(), null))
                        <p class="alert alert-info">{{ trans('reviews.already_reviewed') }}</p>
                    @else
                        <button type="button" class="btn btn-primary px-3 py-2" data-bs-toggle="modal" data-bs-target="#addreviewmodal">{{ trans('reviews.write') }}</button>
                        <div class="modal fade" id="addreviewmodal" tabindex="-1" aria-labelledby="addreviewmodalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h2 class="modal-title h4" id="addreviewmodalLabel">{{ trans('reviews.write') }}</h2>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('labels.close') }}"></button>
                                    </div>
                                    <form action="{{ url('/add-review') }}" method="POST" class="mb-0">
                                        @csrf
                                        <div class="modal-body">
                                            <fieldset class="mb-3 text-center">
                                                <legend class="fs-6">{{ trans('reviews.ratings') }}</legend>
                                                <div class="star-rating">
                                                    @for ($star = 5; $star >= 1; $star--)
                                                        <input type="radio" id="restaurant-review-star-{{ $star }}" name="ratting" value="{{ $star }}" required {{ (int) old('ratting') === $star ? 'checked' : '' }}>
                                                        <label for="restaurant-review-star-{{ $star }}" aria-label="{{ trans('reviews.stars', ['count' => $star]) }}"><i class="fa-solid fa-star" aria-hidden="true"></i></label>
                                                    @endfor
                                                </div>
                                                @error('ratting')<p class="text-danger">{{ $message }}</p>@enderror
                                            </fieldset>
                                            <label for="restaurant-review-comment" class="form-label">{{ trans('reviews.comment') }}</label>
                                            <textarea id="restaurant-review-comment" name="comment" rows="4" class="form-control" maxlength="255" aria-describedby="restaurant-review-help">{{ old('comment') }}</textarea>
                                            <p id="restaurant-review-help" class="text-muted small mt-1">{{ trans('reviews.comment_help') }}</p>
                                            @error('comment')<p class="text-danger">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ trans('labels.close') }}</button>
                                            <button type="submit" class="btn btn-primary">{{ trans('reviews.submit') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                @elseif (!Auth::check())
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#review-login-modal">{{ trans('reviews.write') }}</button>
                @endif
            @endif
        </div>
    </section>
    @include('web.subscribeform')
@endsection
