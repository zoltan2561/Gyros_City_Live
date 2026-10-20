@extends('web.layout.default')
@section('page_title', '| Étlap')
@section('styles')
<link rel="stylesheet" href="{{ url('public/city/menu.css') }}?v=20261020-2">
@endsection
@section('content')
<div class="city-menu-page">
    <section class="city-menu-intro city-menu-overview-intro"><div class="container"><div><p class="city-eyebrow">Gyros City · Étlap</p><h1>Mihez van kedved?</h1><p>Gyros, burger vagy valami más? Innen indul a rendelésed.</p></div><a class="city-menu-back" href="{{ route('cart') }}">Kosaram megnyitása →</a></div></section>
    <section class="container city-menu-overview" aria-label="Étlap kategóriái">
        @foreach (helper::get_categories() as $category)
        <a class="city-menu-category-card" href="{{ url('/menu?category='.$category->slug) }}">
            <div class="city-menu-category-photo"><img src="{{ helper::image_path($category->image) }}" width="480" height="300" alt="" loading="lazy"></div>
            <div class="city-menu-category-caption"><div><p>{{ $category->item_info->count() }} tétel</p><h2>{{ $category->category_name }}</h2></div><span aria-hidden="true">↗</span></div>
        </a>
        @endforeach
    </section>
    <div class="container city-menu-help"><p>Inkább telefonon rendelnél?</p><a href="tel:+36205976297">06 20 597 6297 ↗</a></div>
</div>
@endsection
