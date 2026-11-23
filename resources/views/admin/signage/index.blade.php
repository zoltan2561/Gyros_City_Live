@extends('admin.theme.default')
@section('styles')
<link rel="stylesheet" href="{{ url('public/signage/backgrounds.css') }}?v={{ filemtime(public_path('signage/backgrounds.css')) }}">
<link rel="stylesheet" href="{{ url('public/signage/admin.css') }}?v={{ filemtime(public_path('signage/admin.css')) }}">
@endsection
@section('content')
<div class="container-fluid py-4 signage-admin">
<header class="signage-hero d-flex justify-content-between flex-wrap gap-3 mb-3"><div><h1 class="h3 mb-1">TV műsor</h1><p class="text-muted mb-0">Válassz listát, szerkeszd, és mentsd a TV-kre. Az árak és az elérhetőség élőben követik a katalógust.</p></div>
<a class="btn btn-outline-primary align-self-start" href="{{ url('signage/') }}" target="_blank" rel="noopener">Lejátszó megnyitása</a></header>
@if(!$ready)
<div class="alert alert-warning" role="alert">A TV-modul táblái még nincsenek telepítve.</div>
@else
<section class="signage-tv-strip mb-4" aria-label="Kijelzők állapota">
@forelse($screens as $screen)
@php $online=$screen->last_seen_at&&$screen->last_seen_at->gt(now()->subSeconds(180)); $listName=isset($screenPlaylists[$screen->id])?$playlists->firstWhere('id',$screenPlaylists[$screen->id])?->name:null; @endphp
<a class="signage-tv-chip {{ $online?'is-online':'is-offline' }}" href="{{ route('admin.signage',['tab'=>'screens']) }}#tv-{{ $screen->id }}"><span class="signage-dot" aria-hidden="true"></span><span><strong class="d-block">{{ $screen->name }}</strong><small>{{ $online?'Kapcsolódva':'Nincs friss kapcsolat' }} · {{ $listName?:'Nincs kiadott lista' }}</small></span></a>
@empty
<a class="signage-tv-chip is-empty" href="{{ route('admin.signage',['tab'=>'screens']) }}"><span class="signage-dot" aria-hidden="true"></span><span><strong class="d-block">Még nincs TV</strong><small>Kijelző létrehozása és párosítása</small></span></a>
@endforelse
</section>
<p class="small text-muted mb-4">Árprofil: katalógusár. A helyszíni fizetendő ár és a csomagolási díj külön ellenőrizendő.</p>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" id="signage-errors"><p>A mentéshez javítsd az alábbiakat:</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<nav class="nav nav-pills gap-2 mb-4" aria-label="TV modul">
@foreach(['playlists'=>'Lejátszási listák','scenes'=>'Jelenetek','media'=>'Médiatár','screens'=>'Kijelzők'] as $key=>$label)
<a class="nav-link {{ $tab===$key?'active':'' }}" href="{{ route('admin.signage',['tab'=>$key]) }}" @if($tab===$key)aria-current="page"@endif>{{ $label }}</a>
@endforeach
</nav>
@include('admin.signage.'.$tab)
@endif
</div>
@endsection
@section('script')
<script src="{{ url('public/signage/admin.js') }}?v={{ filemtime(public_path('signage/admin.js')) }}"></script>
<script src="{{ url('public/signage/export.js') }}?v={{ filemtime(public_path('signage/export.js')) }}"></script>
@endsection
