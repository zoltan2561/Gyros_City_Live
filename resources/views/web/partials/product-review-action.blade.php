@if (Auth::user() && (int) Auth::user()->type === 2)
    @if (helper::check_review_exist(Auth::id(), $getitemdata->id))
        <p class="text-muted mb-0">{{ trans('reviews.already_reviewed_product') }}</p>
    @else
        <button type="button" class="btn btn-outline-primary fs-7 write-review"
            data-bs-toggle="modal" data-bs-target="#reviewModal"
            data-item-id="{{ $getitemdata->id }}"
            data-item-name="{{ $getitemdata->item_name }}"
            data-item-image="{{ helper::image_path($getitemdata->item_image?->image_name ?? helper::appdata()->no_data_image) }}">{{ trans('reviews.write_product') }}</button>
    @endif
@elseif (!Auth::check())
    <p class="text-muted mb-2">{{ trans('reviews.login_help') }}</p>
    @if (helper::checkaddons('customer_login') && (int) helper::appdata()->login_required === 1)
        <a class="btn btn-outline-primary fs-7" href="{{ url('/login') }}">{{ trans('labels.login') }}</a>
    @endif
@endif
