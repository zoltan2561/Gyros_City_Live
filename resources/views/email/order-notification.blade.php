@extends('email.order-layout')
@section('order-content')
    <p style="margin:0 0 12px;">Kedves{{ $recipientName ? ' '.$recipientName : ' Vendég' }}!</p>
    <p style="margin:0 0 24px;">{{ $messageText }}</p>
    <div style="padding:16px;background:#faf7f0;border-left:4px solid #c60053;">
        <p style="margin:0;font-size:13px;color:#625b51;">Rendelési szám</p>
        <p style="margin:4px 0 0;font-size:22px;font-weight:bold;">{{ $summary['number'] }}</p>
        <p style="margin:8px 0 0;">{{ $summary['type'] }}</p>
        @if ($summary['name'])<p style="margin:4px 0 0;">Rendelő: {{ $summary['name'] }}</p>@endif
        @if ($summary['address'])<p style="margin:4px 0 0;">{{ $summary['address'] }}</p>@endif
        @if ($summary['schedule'])<p style="margin:4px 0 0;">Kért időpont: {{ $summary['schedule'] }}</p>@endif
    </div>
    @if ($summary['items'])
        <h2 style="margin:24px 0 12px;font-size:18px;">A rendelésed</h2>
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;table-layout:fixed;font-size:15px;line-height:1.5;">
            <thead><tr><th scope="col" align="left" style="padding:10px 0;border-bottom:2px solid #e7e0d5;width:70%;">Tétel</th><th scope="col" align="right" style="padding:10px 0;border-bottom:2px solid #e7e0d5;">Összeg</th></tr></thead>
            <tbody>
            @foreach ($summary['items'] as $item)
                <tr><td style="padding:14px 12px 14px 0;border-bottom:1px solid #e7e0d5;vertical-align:top;overflow-wrap:anywhere;">
                    <strong>{{ $item['qty'] }} × {{ $item['name'] }}</strong>
                    @if ($item['addons'])<p style="margin:4px 0 0;font-size:13px;color:#625b51;">Kiegészítők: {{ $item['addons'] }}</p>@endif
                    @if ($item['extras'])<p style="margin:4px 0 0;font-size:13px;color:#625b51;">Extrák: {{ $item['extras'] }}</p>@endif
                </td><td align="right" style="padding:14px 0;border-bottom:1px solid #e7e0d5;vertical-align:top;">{{ $item['total'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;font-size:15px;">
        @if ($summary['discount'])<tr><td style="padding:5px 0;">Kedvezmény</td><td align="right">−{{ $summary['discount'] }}</td></tr>@endif
        @if ($summary['delivery'])<tr><td style="padding:5px 0;">Kiszállítás</td><td align="right">{{ $summary['delivery'] }}</td></tr>@endif
        <tr><td style="padding:14px 0 0;font-size:19px;font-weight:bold;">Végösszeg</td><td align="right" style="padding-top:14px;font-size:19px;font-weight:bold;color:#a50045;">{{ $summary['total'] }}</td></tr>
    </table>
    @if ($summary['notes'])<h2 style="margin:24px 0 8px;font-size:18px;">Megjegyzésed</h2><p style="margin:0;white-space:pre-line;">{{ $summary['notes'] }}</p>@endif
@endsection
