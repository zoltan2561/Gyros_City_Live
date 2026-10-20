<!doctype html>
<html lang="hu">
<head><meta name="csrf-token" content="{{ csrf_token() }}">@yield('styles')</head>
<body>@yield('content')@yield('script')</body>
</html>
