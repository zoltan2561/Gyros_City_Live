<div id="review_settings" class="card border-0 mb-4">
    <div class="card-body">
        <h2 class="h5">{{ trans('reviews.settings') }}</h2>
        <form method="post" action="{{ url('admin/review-settings/update') }}">
            @csrf
            <div class="form-check form-switch mb-2">
                <input type="checkbox" class="form-check-input" id="reviews-enabled" name="reviews_enabled" value="1" @checked((int)($getsettings->reviews_enabled ?? 2) === 1) aria-describedby="reviews-enabled-help">
                <label class="form-check-label fw-bold" for="reviews-enabled">{{ trans('reviews.enabled') }}</label>
            </div>
            <p id="reviews-enabled-help" class="text-muted small">{{ trans('reviews.enable_help') }}</p>
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="reviews-auto-approve" name="review_approved_status" value="1" @checked((int)$getsettings->review_approved_status === 1)>
                <label class="form-check-label" for="reviews-auto-approve">{{ trans('reviews.auto_approve') }}</label>
            </div>
            <p class="text-muted small">{{ trans('reviews.auto_help') }}</p>
            <fieldset class="mb-3">
                <legend class="small">{{ trans('reviews.ratings') }}</legend>
                <div class="d-flex gap-3 flex-wrap">
                    @foreach (range(1, 5) as $rating)
                        <label class="form-check" for="reviews-auto-{{ $rating }}">
                            <input type="checkbox" class="form-check-input" name="review_auto_approved[]" value="{{ $rating }}" id="reviews-auto-{{ $rating }}" @checked(in_array((string)$rating, explode(',', (string)$getsettings->review_auto_approved), true))>
                            {{ trans('reviews.stars', ['count' => $rating]) }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <button class="btn btn-primary" type="submit">{{ trans('reviews.save') }}</button>
        </form>
    </div>
</div>
