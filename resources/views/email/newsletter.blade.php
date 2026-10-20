<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $letterSubject }}</title></head>
<body style="margin:0;background:#faf7f0;font-family:Arial,sans-serif;color:#211e1a;">
    <div style="max-width:600px;margin:24px auto;padding:28px;background:#fff;border-radius:12px;">
        <p style="color:#c60053;font-weight:bold;">{{ config('mail.from.name') }}</p>
        <h1 style="font-size:24px;line-height:1.3;overflow-wrap:anywhere;">{{ $letterSubject }}</h1>
        <div style="line-height:1.7;overflow-wrap:anywhere;">{!! nl2br(e($letterBody)) !!}</div>
        <hr style="border:0;border-top:1px solid #e7e0d5;margin:28px 0;">
        @if ($unsubscribeUrl)
            <p style="font-size:12px;color:#706b63;">{{ trans('newsletter.footer') }} <a href="{{ $unsubscribeUrl }}" style="color:#c60053;">{{ trans('newsletter.unsubscribe') }}</a></p>
        @else
            <p style="font-size:12px;color:#706b63;">{{ trans('newsletter.test_footer') }}</p>
        @endif
    </div>
</body>
</html>
