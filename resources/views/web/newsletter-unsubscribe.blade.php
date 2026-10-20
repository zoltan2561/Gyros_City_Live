<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>{{ trans('newsletter.unsubscribe') }}</title></head>
<body style="font-family:Arial,sans-serif;background:#faf7f0;color:#211e1a;margin:0;padding:24px;">
    <main style="max-width:540px;margin:40px auto;padding:28px;background:#fff;border-radius:12px;">
        <h1>{{ trans('newsletter.unsubscribe') }}</h1>
        @if ($done)
            <p>{{ trans('newsletter.unsubscribed') }}</p>
        @else
            <p>{{ trans('newsletter.unsubscribe_help') }}</p>
            <form method="post" action="{{ route('newsletter.unsubscribe.confirm', $token) }}">
                @csrf
                <button type="submit" style="background:#c60053;color:#fff;border:0;border-radius:8px;padding:14px 20px;cursor:pointer;">{{ trans('newsletter.unsubscribe') }}</button>
            </form>
        @endif
        <p><a href="{{ url('/') }}">{{ trans('newsletter.back_home') }}</a></p>
    </main>
</body>
</html>
