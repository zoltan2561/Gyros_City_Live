<div class="city-empty-state">
    <p class="city-empty-title">{{ $emptyTitle ?? trans('reviews.empty_content') }}</p>
    @if (!empty($emptyDescription))<p class="city-empty-description">{{ $emptyDescription }}</p>@endif
    @if (!empty($emptyLink) && !empty($emptyLinkLabel))
        <a class="btn btn-outline-dark" href="{{ $emptyLink }}">{{ $emptyLinkLabel }}</a>
    @endif
</div>
