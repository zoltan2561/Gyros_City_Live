{{ $letterSubject }}

{{ $letterBody }}

@if ($unsubscribeUrl)
{{ trans('newsletter.footer') }}
{{ trans('newsletter.unsubscribe') }}: {{ $unsubscribeUrl }}
@else
{{ trans('newsletter.test_footer') }}
@endif
