<!doctype html>
<html lang="hu" dir="ltr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#faf7f0;color:#211e1a;font-family:Arial,Helvetica,sans-serif;">
<div lang="hu" dir="ltr" style="padding:24px 12px;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;overflow:hidden;mso-hide:all;">{{ $messageText ?? $message_text ?? $title }}</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px;margin:0 auto;background:#ffffff;border:1px solid #e7e0d5;border-radius:12px;">
        <tr><td style="padding:24px;background:#171717;color:#ffffff;border-radius:12px 12px 0 0;">
            <p style="margin:0;font-size:23px;font-weight:bold;">GYROS <span style="color:#ff82b5;">CITY</span></p>
            <p style="margin:6px 0 0;font-size:13px;color:#eeeeee;">Rendelési értesítő</p>
        </td></tr>
        <tr><td style="padding:28px 24px;font-size:16px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word;">
            <h1 style="margin:0 0 20px;font-size:26px;line-height:1.25;color:#211e1a;">{{ $title }}</h1>
            @yield('order-content')
        </td></tr>
        <tr><td style="padding:20px 24px;border-top:1px solid #e7e0d5;font-size:13px;line-height:1.6;color:#625b51;">
            <p style="margin:0;">Automatikus rendelési értesítés a Gyros Citytől.</p>
            <p style="margin:6px 0 0;"><a href="{{ url('/contactus') }}" style="color:#a50045;text-decoration:underline;">Az étterem elérhetőségei</a></p>
        </td></tr>
    </table>
</div>
</body>
</html>
