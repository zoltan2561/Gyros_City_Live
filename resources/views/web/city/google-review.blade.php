@php
    $googleReviewUrl = trim((string) (helper::appdata()->google_review_url ?? ''));
    $validGoogleReviewUrl = filter_var($googleReviewUrl, FILTER_VALIDATE_URL)
        && parse_url($googleReviewUrl, PHP_URL_SCHEME) === 'https';
@endphp
@if ($validGoogleReviewUrl)
    <section class="city-google-review" aria-labelledby="city-google-review-title">
        <div class="container city-google-review-inner">
            <div>
                <p class="city-eyebrow">Mondd el, milyen volt</p>
                <h2 id="city-google-review-title">Értékelj minket a Google-on.</h2>
                <p>Hogy ízlett? Oszd meg a tapasztalatodat, örülünk a visszajelzésednek.</p>
            </div>
            <a class="city-button" href="{{ $googleReviewUrl }}" target="_blank" rel="noopener noreferrer">
                Értékelek a Google-on <span aria-hidden="true">↗</span>
            </a>
        </div>
    </section>
@endif
