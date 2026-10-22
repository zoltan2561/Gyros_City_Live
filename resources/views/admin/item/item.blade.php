@extends('admin.theme.default')
@section('styles')
<link rel="stylesheet" href="{{ url('public/city/admin-products.css') }}?v={{ filemtime(public_path('city/admin-products.css')) }}">
@endsection
@section('content')
<div class="container-fluid products-admin" id="products-admin"
     data-save-url="{{ url('admin/item/quick-update') }}" data-reorder-url="{{ url('admin/item/reorder_item') }}"
     data-visibility-key="gycity:product-visibility:{{ url('admin/item') }}:{{ Auth::id() }}"
     data-demo="{{ env('Environment') === 'sendbox' ? '1' : '0' }}">
    <div class="products-heading">
        <div><p class="products-eyebrow">ÉTLAP KEZELÉSE</p><h1>Termékek <span>{{ $getitem->count() }}</span></h1>
            <p class="products-intro">Árak, elérhetőség és mai ajánlatok egy helyen.</p></div>
        <div class="products-heading-actions">
            @if (helper::checkaddons('product_import'))<a class="btn btn-outline-primary" href="{{ route('import') }}">Importálás</a>@endif
            <a class="btn btn-primary" href="{{ url('admin/item/add') }}"><i class="fa-solid fa-plus" aria-hidden="true"></i> Új termék</a>
        </div>
    </div>
    <div class="products-summary" aria-label="Termékek áttekintése">
        <button type="button" data-filter-state="" class="is-selected"><span>Összes termék</span><strong>{{ $getitem->count() }}</strong></button>
        <button type="button" data-filter-state="active"><span>Aktív termék</span><strong>{{ $getitem->filter(fn ($item) => (int) $item->item_status === 1 && !$item->today_unavailable)->count() }}</strong></button>
        <button type="button" data-filter-state="today"><span>Mára elfogyott</span><strong>{{ $getitem->where('today_unavailable', 1)->count() }}</strong></button>
        <button type="button" data-filter-state="paused"><span>Szüneteltetve</span><strong>{{ $getitem->filter(fn ($item) => (int) $item->item_status !== 1 && !$item->today_unavailable)->count() }}</strong></button>
    </div>
    <div id="products-feedback" class="products-feedback" role="status" aria-live="polite" hidden></div>
    <section class="products-panel" aria-label="Terméklista">
        <form class="products-filters" id="products-filters" role="search">
            <div class="products-search"><label for="product-search">Keresés</label><div><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="product-search" placeholder="Terméknév, kategória vagy azonosító…" autocomplete="off"></div></div>
            <div><label for="product-category">Kategória</label><select id="product-category"><option value="">Minden kategória</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->category_name }}{{ (int) $category->is_available !== 1 ? ' (inaktív)' : '' }}</option>@endforeach
            </select></div>
            <div><label for="product-state">Elérhetőség</label><select id="product-state"><option value="">Minden állapot</option><option value="active">Aktív</option><option value="today">Mára elfogyott</option><option value="paused">Szüneteltetve</option></select></div>
            <div><label for="product-featured">Mai ajánlat</label><select id="product-featured"><option value="">Mindegyik</option><option value="1">Mai ajánlatok</option><option value="2">Nincs kiemelve</option></select></div>
            <button class="products-reset" type="reset">Szűrők törlése</button>
        </form>
        <div class="products-list-tools">
            <div><p id="products-result-count" aria-live="polite"></p><small id="products-reorder-reason" hidden>Sorrendezéshez előbb töröld a szűrőket.</small></div>
            <div class="products-tools">
                <label class="products-visibility" for="products-show-unavailable"><input id="products-show-unavailable" type="checkbox" checked> Nem elérhető termékek is</label>
                <label for="product-sort" class="visually-hidden">Rendezés</label><select id="product-sort"><option value="menu">Étlap sorrendje</option><option value="name">Név: A–Z</option><option value="price-asc">Ár: növekvő</option><option value="price-desc">Ár: csökkenő</option><option value="updated">Legutóbb módosított</option></select>
                <div id="products-export" aria-label="Szűrt lista exportálása"></div>
                <button type="button" class="btn btn-outline-secondary" id="products-reorder">Sorrend módosítása</button>
            </div>
        </div>
        <div id="products-reorder-help" class="products-reorder-help" hidden>
            <span>Húzd a termékeket a fogantyúval, vagy használd a fel/le gombokat. A sorrend csak mentéskor változik.</span>
            <div><button type="button" class="btn btn-outline-secondary" id="products-reorder-cancel">Mégse</button><button type="button" class="btn btn-primary" id="products-reorder-save">Sorrend mentése</button></div>
        </div>
        <div class="products-table-wrap">@include('admin.item.table')</div>
    </section>
    <div class="modal fade products-editor" id="product-editor" tabindex="-1" aria-labelledby="product-editor-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form id="product-editor-form">
                <div class="modal-header"><div><p class="products-eyebrow">GYORS SZERKESZTÉS</p><h2 class="modal-title" id="product-editor-title">Termék módosítása</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bezárás"></button></div>
                <div class="modal-body">
                    <p class="products-editor-note">A képeket, összetevőket és extrákat a részletes szerkesztőben találod.</p>
                    <div class="alert alert-danger" id="product-editor-error" role="alert" tabindex="-1" hidden></div>
                    <input type="hidden" name="id"><input type="hidden" name="version">
                    <label for="product-name">Termék neve</label><input id="product-name" name="item_name" class="form-control" maxlength="255" required>
                    <label for="product-edit-category">Kategória</label><select id="product-edit-category" name="cat_id" class="form-select" required>
                        @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->category_name }}{{ (int) $category->is_available !== 1 ? ' (inaktív)' : '' }}</option>@endforeach
                    </select>
                    <div class="products-editor-prices">
                        <div><label for="product-price">Eladási ár ({{ helper::appdata()->currency }})</label><input id="product-price" class="form-control" name="price" type="number" min="0" max="9999999.99" step="0.01" inputmode="decimal" required></div>
                        <div><label for="product-original-price">Eredeti ár (opcionális)</label><input id="product-original-price" class="form-control" name="original_price" type="number" min="0" max="9999999.99" step="0.01" inputmode="decimal"><small>Akció nélkül hagyd üresen.</small></div>
                    </div>
                    <label for="product-availability">Elérhetőség</label><select id="product-availability" name="availability" class="form-select"><option value="active">Aktív – rendelhető</option><option value="today">Mára elfogyott</option><option value="paused">Szüneteltetve – visszakapcsolásig</option></select>
                    <p class="products-editor-note mt-2">Inaktív kategória terméke nem rendelhető az étlapon.</p>
                    <label class="products-featured-check"><input type="checkbox" name="is_featured" value="1"> Mai ajánlatként kiemelve</label>
                </div>
                <div class="modal-footer"><a id="product-full-edit" class="products-full-edit" href="#">Részletes szerkesztés</a><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Mégse</button><button type="submit" class="btn btn-primary" id="product-save">Módosítások mentése</button></div>
            </form>
        </div></div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ url('public/city/admin-products.js') }}?v={{ filemtime(public_path('city/admin-products.js')) }}"></script>
@endsection
