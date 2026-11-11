@php
    $sceneOptions=$scenes->map(fn($scene)=>['id'=>$scene->id,'name'=>$scene->name,'duration'=>$scene->content['duration']??15,'edit_url'=>route('admin.signage',['tab'=>'scenes','edit_scene'=>$scene->id])])->values();
    $initialScenes=collect(old('scene_ids',$editingPlaylist?->scene_ids??[]))->map(fn($id)=>$sceneOptions->firstWhere('id',(int)$id)??['id'=>$id,'name'=>'Hiányzó jelenet','duration'=>0])->values();
    $selectedScreens=array_map('strval',session()->hasOldInput('screen_selection')?old('screen_ids',[]):($editingPlaylist?$screens->filter(fn($screen)=>($screenPlaylists[$screen->id]??null)===$editingPlaylist->id)->pluck('id')->all():[]));
@endphp
<div class="row g-4">
    <aside class="col-lg-4" aria-label="Mentett lejátszási listák">
        <div class="card p-3 mb-3">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                <h2 class="h5 mb-0">Lejátszási listák</h2>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.signage',['tab'=>'playlists','new_playlist'=>1]) }}">Új lista</a>
            </div>
            @if($playlists->isNotEmpty())
            <label class="form-label d-lg-none" for="playlist-switch">Választott lista</label>
            <select class="form-select d-lg-none" id="playlist-switch">
                @if(!$editingPlaylist)<option value="" selected disabled>Új lejátszási lista</option>@endif
                @foreach($playlists as $list)<option value="{{ route('admin.signage',['tab'=>'playlists','edit_playlist'=>$list->id]) }}" @selected($editingPlaylist?->id===$list->id)>{{ $list->name }}</option>@endforeach
            </select>
            @endif
            <div class="list-group playlist-library {{ $playlists->isNotEmpty()?'d-none d-lg-block':'' }}">
                @forelse($playlists as $list)
                <a class="list-group-item list-group-item-action {{ $editingPlaylist?->id===$list->id?'active':'' }}" href="{{ route('admin.signage',['tab'=>'playlists','edit_playlist'=>$list->id]) }}" @if($editingPlaylist?->id===$list->id)aria-current="page"@endif>
                    <strong class="d-block">{{ $list->name }}</strong>
                    <span class="small">{{ count($list->scene_ids) }} jelenet · folyamatos ismétlés</span>
                </a>
                @empty
                <p class="text-muted mb-0">Még nincs lista. Adj nevet az elsőnek, és válassz hozzá jeleneteket.</p>
                @endforelse
            </div>
        </div>
        <p class="small text-muted">A TV sorban végigjátssza a listát, majd kezdi elölről. Nincs kezdési vagy befejezési idő.</p>
    </aside>
    <div class="col-lg-8">
        <form class="card p-4" method="post" action="{{ url('admin/signage/playlists') }}" id="playlist-form" novalidate>
            @csrf
            <input type="hidden" name="id" value="{{ $editingPlaylist?->id }}">
            <input type="hidden" name="version" value="{{ old('version',$editingPlaylist?->editVersion()) }}">
            <input type="hidden" name="screen_selection" value="1">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">{{ $editingPlaylist?'Lista szerkesztése':'Új lejátszási lista' }}</h2>
                @if($editingPlaylist)
                <a class="btn btn-outline-secondary btn-sm" href="{{ url('admin/signage/preview/'.$editingPlaylist->id) }}" target="_blank" rel="noopener">Mentett lista előnézete</a>
                @endif
            </div>
            <label for="playlist-name" class="form-label">Lista neve</label>
            <input id="playlist-name" name="name" class="form-control mb-4" value="{{ old('name',$editingPlaylist?->name) }}" required maxlength="120" placeholder="Például: Pult feletti TV">
            <label for="playlist-scene" class="form-label">Jelenet hozzáadása</label>
            <div class="d-flex gap-2 mb-3">
                <select class="form-select" id="playlist-scene" @disabled($scenes->isEmpty())>
                    <option value="">Válassz jelenetet…</option>
                    @foreach($scenes as $scene)<option value="{{ $scene->id }}">{{ $scene->name }}</option>@endforeach
                </select>
                <button type="button" class="btn btn-outline-primary" id="add-playlist-scene" disabled>Hozzáadás</button>
            </div>
            @if($scenes->isEmpty())<p class="small"><a href="{{ route('admin.signage',['tab'=>'scenes']) }}">Először hozz létre egy jelenetet.</a></p>@endif
            <ol id="playlist-scenes" class="list-group list-group-numbered mb-2" aria-label="Jelenetek lejátszási sorrendje"></ol>
            <p id="playlist-empty" class="text-muted playlist-empty">Add hozzá az első jelenetet a fenti választóból.</p>
            <p class="small text-muted mb-4">A sorrend a Fel / Le gombokkal vagy húzással változtatható. A Tartalom külön lapon nyitja meg a jelenet szerkesztőjét; utána itt mentsd a listát a TV-k frissítéséhez.</p>
            <fieldset class="playlist-targets mb-4">
                <legend class="h6">Frissítendő TV-k</legend>
                @forelse($screens as $screen)
                <label class="form-check mb-2">
                    <input class="form-check-input" name="screen_ids[]" value="{{ $screen->id }}" type="checkbox" @checked(in_array((string)$screen->id,$selectedScreens,true))>
                    <span class="form-check-label">{{ $screen->name }}</span>
                </label>
                @empty
                <p class="small text-muted">Még nincs TV. A listát már összeállíthatod, a kijelzőt később a <a href="{{ route('admin.signage',['tab'=>'screens']) }}">Kijelzők oldalon</a> adhatod hozzá.</p>
                @endforelse
                @if($screens->isNotEmpty())<p class="small text-muted mb-0">A kijelölt TV-k teljes letöltés után automatikusan átveszik ezt a listát. A többi TV folytatja a jelenlegi műsort.</p>@endif
            </fieldset>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <span class="small text-muted" id="playlist-summary" role="status"></span>
                <button class="btn btn-primary" id="playlist-save" disabled>Lista mentése</button>
            </div>
        </form>
        @if($releases->isNotEmpty() && $screens->isNotEmpty())
        <details class="card p-4 mt-3">
            <summary>Korábbi megjelenés visszaállítása</summary>
            <form class="mt-3" method="post" action="{{ url('admin/signage/rollback') }}" data-confirm="A kijelölt TV a korábbi megjelenést tölti le. Az árak továbbra is a jelenlegi katalógusból frissülnek." novalidate>
                @csrf
                <label for="rollback-screen" class="form-label">Kijelző</label>
                <select id="rollback-screen" class="form-select mb-3" name="screen_id">@foreach($screens as $screen)<option value="{{ $screen->id }}">{{ $screen->name }}</option>@endforeach</select>
                <label for="rollback-release" class="form-label">Korábbi megjelenés</label>
                <select id="rollback-release" class="form-select mb-3" name="release_id">@foreach($releases as $release)<option value="{{ $release->id }}">{{ $release->snapshot['name']??'Korábbi lista' }} · {{ $release->created_at->format('m.d. H:i') }}</option>@endforeach</select>
                <button class="btn btn-outline-primary">Megjelenés visszaállítása</button>
            </form>
        </details>
        @endif
    </div>
</div>
<script type="application/json" id="playlist-initial">@json($initialScenes)</script>
<script type="application/json" id="playlist-options">@json($sceneOptions)</script>
