@extends('web.layout.default')
@section('page_title', '| Kezdőlap')
@section('content')
<div class="city-home">
    <section class="city-hero" aria-labelledby="city-hero-title">
        <img class="city-hero-photo" src="{{ url('public/city/hero-burger.webp') }}" alt="Hamburger tükörtojással, sült krumpli és gyros a Gyros City kínálatából" fetchpriority="high" width="1774" height="887">
        <div class="container city-hero-inner">
            <div class="city-hero-copy">
                <p class="city-eyebrow"><span></span> Vásárosnamény · Gyros City</p>
                <h1 id="city-hero-title">Ha éhes vagy,<br><em>jó helyen jársz.</em></h1>
                <p class="city-hero-description">Gyros, burger, pizza, frissensült vagy valami igazán kiadós?<br>Válaszd ki a kedvenced, mi elkészítjük.</p>
                <div class="city-hero-actions"><a href="{{ route('categories') }}" class="city-button">Mutasd az étlapot <span aria-hidden="true">↗</span></a><a class="city-hero-phone" href="tel:+36205976297"><small>Inkább telefonon?</small>06 20 597 6297</a></div>
            </div>
            <span class="city-hero-caption">A kedvenceid egy helyen.</span>
        </div>
    </section>
    <div class="city-service-strip"><div class="container"><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Ifjúság út 51/b.</span><span><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Helyben vagy elvitelre</span><a href="{{ route('categories') }}"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> Rendelj házhoz <span aria-hidden="true">→</span></a></div></div>
    <section class="city-section city-categories" aria-labelledby="city-categories-title">
        <div class="container">
            <div class="city-section-heading"><div><p class="city-eyebrow">Mi legyen ma?</p><h2 id="city-categories-title">Találd meg a kedvenced.</h2></div><a href="{{ route('categories') }}" class="city-text-link">Teljes étlap <span aria-hidden="true">↗</span></a></div>
            <div class="city-category-grid">
                @foreach (helper::get_categories()->take(8) as $category)
                    <a class="city-category" href="{{ url('/menu?category='.$category->slug) }}"><img src="{{ helper::image_path($category->image) }}" alt="" loading="lazy" width="120" height="120"><span>{{ $category->category_name }}</span><span class="city-category-arrow" aria-hidden="true">↗</span></a>
                @endforeach
            </div>
        </div>
    </section>
    @php $activeGiftPromotions = app(\App\Services\GiftPromotions::class)->active(); @endphp
    @if ($activeGiftPromotions->isNotEmpty())
    <section class="city-promotion-strip"><div class="container"><span class="city-eyebrow">Most ajándék is járhat mellé</span><div>@foreach ($activeGiftPromotions as $promotion)<p>{{ $promotion->name }}</p>@endforeach</div><a href="{{ route('categories') }}" class="city-button">Nézem az étlapot ↗</a></div></section>
    @endif
    <section class="city-section city-favorites" aria-labelledby="city-favorites-title">
        <div class="container">
            <div class="city-section-heading"><div><p class="city-eyebrow">Egy jó ebéd itt kezdődik</p><h2 id="city-favorites-title">Ismerős ízek. Jó választások.</h2></div><a href="{{ route('categories') }}" class="city-text-link">Összes étel ↗</a></div>
            <div class="row g-4">@foreach ($topitemlist->take(6) as $itemdata) @include('web.city.item-card') @endforeach</div>
        </div>
    </section>
    <section class="city-visit"><div class="container city-visit-inner"><div><p class="city-eyebrow">Itt találsz minket</p><h2>Ugorj be.<br>Vagy rendelj haza.</h2><p>Vásárosnamény, Ifjúság út 51/b.</p><div class="d-flex flex-wrap gap-3"><a class="city-button" href="{{ route('contact-us') }}">Elérhetőség és útvonal ↗</a><a class="city-text-link" href="tel:+36205976297">06 20 597 6297</a></div></div><div class="city-visit-note"><span class="city-wordmark">Gyros<span>City</span></span><p>Burgerek, gyrosok és még sok kedvenc.<br>Találkozunk a Cityben!</p><a href="https://www.facebook.com/gyroscity" target="_blank" rel="noopener noreferrer" class="city-text-link">A Facebookon is megtalálsz ↗</a></div></div></section>
    @include('web.city.google-review')
</div>
@endsection
