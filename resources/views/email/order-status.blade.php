@extends('email.order-layout')
@section('order-content')
    <p style="margin:0 0 12px;">Kedves{{ $name ? ' '.$name : ' Vendég' }}!</p>
    <p style="margin:0;">{{ $message_text }}</p>
@endsection
