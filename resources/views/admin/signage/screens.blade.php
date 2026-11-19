<div class="row g-4"><div class="col-xl-4 order-2">
<form class="card p-4 mb-4" method="post" action="{{ url('admin/signage/screens') }}" novalidate>@csrf
<h2 class="h5">Új kijelző</h2><label for="screen-name" class="form-label">Név</label><input id="screen-name" name="name" value="{{ old('name') }}" maxlength="120" class="form-control mb-3" required>
<label for="screen-location" class="form-label">Hely</label><input id="screen-location" name="location" value="{{ old('location') }}" maxlength="120" class="form-control mb-3" placeholder="Például: pult bal">
<label for="screen-ttl" class="form-label">Offline árérvényesség (mp)</label><input id="screen-ttl" type="number" name="offline_price_seconds" min="60" max="86400" class="form-control mb-3" value="{{ old('offline_price_seconds',1800) }}"><p class="small text-muted">Az árak legfeljebb ennyi ideig maradnak érvényesek kapcsolat nélkül. Az akció lejárata ezt rövidítheti.</p>
<button class="btn btn-primary">Kijelző létrehozása</button></form>
<form class="card p-4" method="post" action="{{ url('admin/signage/pair') }}" novalidate>@csrf
<h2 class="h5">TV párosítása</h2><p class="text-muted">Nyisd meg a lejátszót a TV-n, majd írd ide a képernyőjén látható kódot. A kód 10 percig érvényes.</p>
<label for="pair-screen" class="form-label">Kijelző</label><select id="pair-screen" name="screen_id" class="form-select mb-3">@foreach($screens as $screen)<option value="{{ $screen->id }}">{{ $screen->name }}</option>@endforeach</select>
<label for="pair-code" class="form-label">Nyolcjegyű kód</label><input id="pair-code" name="code" inputmode="numeric" autocomplete="off" maxlength="8" class="form-control mb-3" required>
<button class="btn btn-primary" @disabled($screens->isEmpty())>Párosítás</button><p class="small text-muted mt-2 mb-0">Újrapárosításkor a korábbi lejátszó hozzáférése megszűnik.</p></form>
</div><div class="col-xl-8 order-1">
@forelse($screens as $screen)
<article id="tv-{{ $screen->id }}" class="card p-4 mb-3"><div class="d-flex justify-content-between gap-2"><h2 class="h5">{{ $screen->name }}</h2><span class="badge {{ $screen->last_seen_at && $screen->last_seen_at->gt(now()->subSeconds(180))?'bg-success':'bg-secondary' }} align-self-start">{{ $screen->last_seen_at && $screen->last_seen_at->gt(now()->subSeconds(180))?'Van friss kapcsolat':'Nincs friss kapcsolat' }}</span></div>
<p class="text-muted">{{ $screen->location ?: 'Hely nincs megadva' }} · Utolsó kapcsolat: {{ $screen->last_seen_at?->timezone('Europe/Budapest')->format('m.d. H:i:s') ?? 'Még nem jelentkezett' }}</p>
@php
    $listName=isset($screenPlaylists[$screen->id])?$playlists->firstWhere('id',$screenPlaylists[$screen->id])?->name:null;
    $reported=$screen->status['active_release_id']??null;
    $playing=$reported?($releaseNames[$reported]??'Kiadás #'.$reported):null;
    $problem=trim((string)($screen->status['error']??''));
@endphp
<dl class="row mb-2 signage-facts">
    <dt class="col-sm-5">A TV utolsó jelzése</dt>
    <dd class="col-sm-7">{{ $playing?:'Még nincs visszaigazolt lejátszás' }}
        @if($problem)<span class="signage-state is-bad">{{ $problem }}</span>@endif
    </dd>
    @if($screen->release_id && (int)$reported !== (int)$screen->release_id)
        <dt class="col-sm-5">Váltásra vár</dt><dd class="col-sm-7">{{ $releaseNames[$screen->release_id]??$listName }}</dd>
    @endif
</dl>
@if($playlists->isNotEmpty())
<form class="mb-3" method="post" action="{{ url('admin/signage/publish') }}">
    @csrf
    <input type="hidden" name="screen_ids[]" value="{{ $screen->id }}">
    <input type="hidden" name="return_tab" value="screens">
    <label class="form-label" for="tv-playlist-{{ $screen->id }}">Lejátszási lista</label>
    <div class="d-flex flex-wrap gap-2">
        <select class="form-select flex-grow-1 w-auto" name="playlist_id" id="tv-playlist-{{ $screen->id }}" required>
            <option value="">Válassz listát</option>
            @foreach($playlists as $playlist)
                <option value="{{ $playlist->id }}" @selected(($screenPlaylists[$screen->id]??null)==$playlist->id)>{{ $playlist->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit">Indítás ezen a TV-n</button>
    </div>
</form>
@endif
<details class="mb-3"><summary>Kijelző beállításai</summary><form method="post" action="{{ url('admin/signage/screens') }}" class="mt-3" novalidate>@csrf<input type="hidden" name="id" value="{{ $screen->id }}"><label for="edit-screen-name-{{ $screen->id }}" class="form-label">Név</label><input class="form-control mb-2" id="edit-screen-name-{{ $screen->id }}" name="name" value="{{ $screen->name }}" maxlength="120" required><label for="edit-screen-location-{{ $screen->id }}" class="form-label">Hely</label><input class="form-control mb-2" id="edit-screen-location-{{ $screen->id }}" name="location" value="{{ $screen->location }}" maxlength="120"><label for="edit-screen-ttl-{{ $screen->id }}" class="form-label">Offline árérvényesség (mp)</label><input class="form-control mb-3" type="number" id="edit-screen-ttl-{{ $screen->id }}" name="offline_price_seconds" min="60" max="86400" value="{{ $screen->offline_price_seconds??1800 }}"><button class="btn btn-outline-primary btn-sm">Beállítások mentése</button></form></details>
@if($screen->token_hash)<form method="post" action="{{ url('admin/signage/revoke') }}" data-confirm="A lejátszó következő online kapcsolata letiltásra kerül. Új párosításra lesz szükség.">@csrf<input type="hidden" name="screen_id" value="{{ $screen->id }}"><button class="btn btn-outline-danger btn-sm">Hozzáférés visszavonása</button></form>@else<p class="small text-muted mb-0">Nincs párosítva.</p>@endif
</article>@empty<div class="card p-4">Még nincs kijelző. A bal oldali űrlappal hozd létre az elsőt.</div>@endforelse
</div></div>
