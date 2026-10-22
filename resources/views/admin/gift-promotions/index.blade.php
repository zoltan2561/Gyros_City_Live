@extends('admin.theme.default')
@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="h3">Ajándékakciók</h1><p class="text-muted mb-0">Ha ebből ennyit rendelnek, ezt adjuk ajándékba.</p></div>
        <a class="btn btn-outline-primary align-self-start" href="{{ route('admin.gift-promotions') }}">Új akció</a>
    </div>
    @if (!$ready)
        <div class="alert alert-warning">Az akciók használatához előbb telepíteni kell a frissítés adatbázis-módosítását.</div>
    @else
    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-4">
        <div class="col-xl-7">
            <form method="post" action="{{ route('admin.gift-promotions.save') }}" class="card p-4" id="gift-promotion-editor">
                @csrf
                <input type="hidden" name="id" value="{{ $editing->id }}">
                <h2 class="h5 mb-4">{{ $editing->exists ? 'Akció szerkesztése' : 'Új ajándékakció' }}</h2>
                <label for="promotion-name" class="form-label">Akció neve (a vásárló is látja)</label>
                <input id="promotion-name" name="name" class="form-control mb-3" maxlength="120" required value="{{ old('name', $editing->name) }}" placeholder="Például: Két pizza mellé ajándék palacsinta">
                @php $triggerType = old('trigger_type', $editing->category_id || !$editing->exists ? 'category' : 'items'); @endphp
                <label for="trigger-type" class="form-label">Miből kell rendelni?</label>
                <select id="trigger-type" name="trigger_type" class="form-select mb-3">
                    <option value="category" @selected($triggerType === 'category')>Egy kategória bármely ételéből</option>
                    <option value="items" @selected($triggerType === 'items')>Kiválasztott ételekből</option>
                </select>
                <div data-trigger-section="category">
                    <label for="trigger-category" class="form-label">Kategória</label>
                    <select id="trigger-category" name="category_id" class="form-select mb-3">
                        <option value="">Válassz kategóriát</option>
                        @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $editing->category_id) === (string) $category->id)>{{ $category->category_name }}</option>@endforeach
                    </select>
                </div>
                <fieldset data-trigger-section="items" class="mb-3">
                    <legend class="fs-6">Ezek az ételek számítanak bele</legend>
                    <input type="search" class="form-control mb-2" placeholder="Étel keresése…" aria-label="Feltételként számolt ételek keresése" data-filter-list="trigger-items">
                    <div id="trigger-items" class="border rounded p-3 city-admin-options">
                        @foreach ($items as $item)<label class="d-block mb-2"><input type="checkbox" name="trigger_item_ids[]" value="{{ $item->id }}" @checked(in_array($item->id, old('trigger_item_ids', $editing->trigger_item_ids ?? [])))> {{ $item->item_name }}</label>@endforeach
                    </div>
                </fieldset>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6"><label for="required-qty" class="form-label">Szükséges darabszám</label><input id="required-qty" type="number" name="required_qty" class="form-control" min="1" max="100" required value="{{ old('required_qty', $editing->required_qty ?? 2) }}"></div>
                    <div class="col-sm-6"><label for="gift-qty" class="form-label">Ajándék darabszáma</label><input id="gift-qty" type="number" name="gift_qty" class="form-control" min="1" max="10" required value="{{ old('gift_qty', $editing->gift_qty ?? 1) }}"></div>
                </div>
                <fieldset class="mb-3">
                    <legend class="fs-6">Miből választhat ajándékot?</legend>
                    <p class="small text-muted">Válaszd ki az ajándék terméket. Ha az ízei az extrák között szerepelnek, a vendég azok közül választ egyet. A kiválasztott íz is ingyen jár, és látszik a rendelésben. A nem elérhető ajándékot nem ajánljuk fel.</p>
                    <input type="search" class="form-control mb-2" placeholder="Ajándék keresése…" aria-label="Ajándék ételek keresése" data-filter-list="gift-items">
                    <div id="gift-items" class="border rounded p-3 city-admin-options">
                        @foreach ($items as $item)<label class="d-block mb-2"><input type="checkbox" name="gift_item_ids[]" value="{{ $item->id }}" @checked(in_array($item->id, old('gift_item_ids', $editing->gift_item_ids ?? [])))> {{ $item->item_name }} @if (!$item->is_orderable)<span class="text-muted">(jelenleg nem elérhető)</span>@endif</label>@endforeach
                    </div>
                </fieldset>
                <label class="d-flex gap-2 mb-3"><input type="checkbox" name="repeat" value="1" @checked(old('repeat', session()->hasOldInput() ? false : $editing->repeat))> Minden teljes darabszám után újra járjon (pl. 4 pizzához 2 ajándék).</label>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6"><label for="starts-at" class="form-label">Első nap (nem kötelező)</label><input id="starts-at" type="date" class="form-control" name="starts_at" value="{{ old('starts_at', $editing->starts_at?->format('Y-m-d')) }}"></div>
                    <div class="col-sm-6"><label for="ends-at" class="form-label">Utolsó nap (nem kötelező)</label><input id="ends-at" type="date" class="form-control" name="ends_at" value="{{ old('ends_at', $editing->ends_at?->format('Y-m-d')) }}"></div>
                </div>
                <p class="small text-muted">A megadott napok is beleszámítanak, magyarországi idő szerint. Több aktív akció együtt is érvényesülhet.</p>
                <label class="d-flex gap-2 mb-4"><input type="checkbox" name="active" value="1" @checked(old('active', session()->hasOldInput() ? false : $editing->active))> Akció bekapcsolva</label>
                <button class="btn btn-primary align-self-start" type="submit">Akció mentése</button>
            </form>
        </div>
        <div class="col-xl-5">
            <h2 class="h5 mb-3">Mentett akciók</h2>
            @forelse ($promotions as $promotion)
                <article class="card p-3 mb-3">
                    <div class="d-flex justify-content-between gap-2"><h3 class="h6">{{ $promotion->name }}</h3><span class="badge {{ $promotion->active ? 'bg-success' : 'bg-secondary' }} align-self-start">{{ $promotion->active ? 'Bekapcsolva' : 'Kikapcsolva' }}</span></div>
                    <p class="mb-2">{{ $promotion->required_qty }} db → {{ $promotion->gift_qty }} db ajándék. {{ $promotion->repeat ? 'Ismétlődő.' : 'Rendelésenként egyszer.' }}</p>
                    <p class="small text-muted">Feltétel: {{ $promotion->category_id ? ($categories->firstWhere('id', $promotion->category_id)?->category_name ?? 'Korábbi kategória') : $items->whereIn('id', $promotion->trigger_item_ids ?? [])->pluck('item_name')->join(', ') }}<br>Ajándék: {{ $items->whereIn('id', $promotion->gift_item_ids ?? [])->pluck('item_name')->join(', ') }}</p>
                    <a href="{{ route('admin.gift-promotions', ['edit' => $promotion->id]) }}" class="btn btn-outline-primary btn-sm align-self-start">Szerkesztés</a>
                </article>
            @empty
                <div class="card p-4 text-muted">Még nincs ajándékakció. A bal oldali űrlappal hozhatod létre az elsőt.</div>
            @endforelse
        </div>
    </div>
    @endif
</div>
<style>.city-admin-options { max-height:230px; overflow:auto } .city-admin-options [hidden] { display:none!important }</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.getElementById('trigger-type');
    if (!type) return;
    function toggle() {
        document.querySelectorAll('[data-trigger-section]').forEach(section => {
            const active = section.dataset.triggerSection === type.value;
            section.hidden = !active;
            section.querySelectorAll('input,select').forEach(input => input.disabled = !active);
        });
    }
    type.addEventListener('change', toggle); toggle();
    document.querySelectorAll('[data-filter-list]').forEach(search => search.addEventListener('input', () => {
        const query = search.value.toLocaleLowerCase('hu');
        document.getElementById(search.dataset.filterList).querySelectorAll('label').forEach(label => label.hidden = !label.textContent.toLocaleLowerCase('hu').includes(query));
    }));
});
</script>
@endsection
