@extends('admin.theme.default')
@section('styles')
<link rel="stylesheet" href="{{ url('public/signage/admin.css') }}">
@endsection
@section('content')
<div class="container-fluid py-4 signage-admin">
<div class="d-flex justify-content-between flex-wrap gap-3 mb-4"><div><h1 class="h3">TV kijelzők</h1><p class="text-muted mb-0">Étlapok, reklámok és kijelzőnkénti műsor.</p></div>
<a class="btn btn-outline-primary align-self-start" href="{{ url('signage/') }}" target="_blank" rel="noopener">Lejátszó megnyitása</a></div>
@if(!$ready)
<div class="alert alert-warning" role="alert">A TV-modul táblái még nincsenek telepítve.</div>
@else
<div class="alert alert-info">Árprofil: katalógusár. A helyszíni fizetendő ár és a csomagolási díj külön ellenőrizendő.</div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" id="signage-errors"><p>A mentéshez javítsd az alábbiakat:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<nav class="nav nav-pills gap-2 mb-4" aria-label="TV modul">
@foreach(['screens'=>'Kijelzők','media'=>'Médiatár','scenes'=>'Jelenetek','playlists'=>'Lejátszási listák','schedules'=>'Időzítés'] as $key=>$label)
<a class="nav-link {{ $tab===$key?'active':'' }}" href="{{ route('admin.signage',['tab'=>$key]) }}" @if($tab===$key)aria-current="page"@endif>{{ $label }}</a>
@endforeach
</nav>
@include('admin.signage.'.$tab)
@endif
</div>
@endsection
@section('script')
<script src="{{ url('public/signage/admin.js') }}?v=1"></script>
@endsection
