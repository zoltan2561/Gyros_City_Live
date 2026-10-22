@extends('web.layout.default')
@section('page_title', '| Étlap · '.($categorydata->category_name ?? ''))
@section('styles')
<link rel="stylesheet" href="{{ url('public/city/menu.css') }}?v={{ filemtime(public_path('city/menu.css')) }}">
@endsection
@section('content')
@php $menuCategories = helper::get_categories(); @endphp
<div class="city-menu-page">
    <section class="city-menu-intro"><div class="container"><div><p class="city-eyebrow">Gyros City · Étlap</p><h1>{{ $categorydata->category_name }}</h1><p>Válassz egy kedvencet, és állítsd össze úgy, ahogy szereted.</p></div><a class="city-menu-back" href="{{ route('categories') }}">← Összes kategória</a></div></section>
    <div class="container city-menu-layout">
        <aside class="city-menu-sidebar">
            <h2>Kategóriák</h2>
            @include('web.city.menu-categories', ['categoryNavClass' => 'd-none d-lg-flex'])
            <details class="city-menu-category-picker d-lg-none">
                <summary><span><small>Kategóriák</small><strong>{{ $categorydata->category_name }}</strong></span><span class="city-menu-picker-arrow" aria-hidden="true">⌄</span></summary>
                @include('web.city.menu-categories', ['categoryNavClass' => ''])
            </details>
        </aside>
        <section class="city-menu-results" aria-labelledby="menu-results-title">
            <div class="city-menu-toolbar">
                <div><p class="city-eyebrow">Kínálatunk</p><h2 id="menu-results-title">{{ $getitemlist->total() }} tétel az étlapon</h2></div>
                <form method="get" action="{{ url('/menu') }}" class="city-menu-search" role="search">
                    <input type="hidden" name="category" value="{{ $categorydata->slug }}">
                    @if (request()->filled('subcategory'))<input type="hidden" name="subcategory" value="{{ request('subcategory') }}">@endif
                    <label for="menu-search" class="visually-hidden">Keresés ebben a kategóriában</label>
                    <input id="menu-search" type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Mit keresel?" autocomplete="off">
                    <button type="submit" aria-label="Étel keresése"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                </form>
            </div>
            @if ($subcategories->isNotEmpty())
            <nav class="city-menu-subcategories" aria-label="Alkategóriák">
                <a href="{{ url('/menu?'.http_build_query(['category'=>$categorydata->slug, 'q'=>request('q')])) }}" @if (!request('subcategory')) aria-current="page" @endif>Összes</a>
                @foreach ($subcategories as $subcategory)
                <a href="{{ url('/menu?'.http_build_query(['category'=>$categorydata->slug,'subcategory'=>$subcategory->slug,'q'=>request('q')])) }}" @if (request('subcategory') === $subcategory->slug) aria-current="page" @endif>{{ $subcategory->subcategory_name }}</a>
                @endforeach
            </nav>
            @endif
            @if (request()->filled('q'))<p class="city-menu-search-note">Keresés: „{{ request('q') }}” <a href="{{ url('/menu?'.http_build_query(['category'=>$categorydata->slug,'subcategory'=>request('subcategory')])) }}">Keresés törlése ×</a></p>@endif
            @if ($getitemlist->isNotEmpty())
            <div class="city-menu-food-grid">
                @foreach ($getitemlist as $itemdata) @include('web.city.item-card') @endforeach
            </div>
            <div class="city-menu-pagination">{{ $getitemlist->appends(request()->query())->links() }}</div>
            <p class="city-menu-availability-note">A szürkített ételek jelenleg nem rendelhetők. Az összetevőiket továbbra is megnézheted.</p>
            @else
            <div class="city-menu-empty"><h3>Most nincs ilyen találat.</h3><p>Próbálj másik ételnevet, vagy válassz a kategóriák közül.</p><a href="{{ url('/menu?category='.$categorydata->slug) }}" class="city-button">Kategória összes étele</a></div>
            @endif
        </section>
    </div>
    <div class="container city-menu-help"><p>Kérdésed van a rendeléshez?</p><a href="tel:+36205976297">06 20 597 6297 ↗</a><a href="{{ route('cart') }}" class="city-button">Megnézem a kosaram →</a></div>
</div>
@endsection
