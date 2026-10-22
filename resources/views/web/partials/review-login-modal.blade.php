@if (!Auth::check() && helper::reviews_enabled())
    <div class="modal fade" id="review-login-modal" tabindex="-1" aria-labelledby="review-login-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="review-login-title">{{ trans('reviews.login_title') }}</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ trans('labels.close') }}"></button>
                </div>
                <div class="modal-body"><p class="mb-0">{{ trans('reviews.login_help') }}</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">{{ trans('labels.close') }}</button>
                    <a class="btn btn-primary" href="{{ url('/login') }}">{{ trans('labels.login') }}</a>
                </div>
            </div>
        </div>
    </div>
@endif
