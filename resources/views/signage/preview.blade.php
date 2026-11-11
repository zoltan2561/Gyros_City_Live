<!doctype html>
<html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gyros City · TV reklámműsor</title>
<link rel="stylesheet" href="{{ url('public/signage/player.css') }}?v={{ filemtime(public_path('signage/player.css')) }}"></head>
<body class="{{ request()->boolean('fullscreen')?'clean-preview':'' }}"><main id="signage-stage" class="signage-stage"></main>
@unless(request()->boolean('fullscreen'))
<aside class="preview-controls" aria-label="Reklámműsor beállításai">
@php $demoPrograms=app(\App\Services\Signage\SignageDemoPrograms::class)->definitions(); @endphp
<label for="preview-program">Műsor</label><select id="preview-program">@foreach($demoPrograms as $program)<option value="{{ $program['id'] }}">{{ $program['name'] }}</option>@endforeach</select>
@php $backdrops=app(\App\Services\Signage\SignageBackgrounds::class)->options(); @endphp
<label id="preview-backdrop-label" for="preview-backdrop" hidden>Háttér</label><select id="preview-backdrop" hidden>@foreach($backdrops as $key=>$option)<option value="{{ $key }}">{{ $option['label'] }}</option>@endforeach</select>
<button type="button" id="preview-pause" aria-pressed="false">Szünet</button>
<button type="button" id="preview-next">Következő jelenet</button>
<button type="button" id="preview-fullscreen">Teljes képernyő</button>
<p>Folyamatos ismétlés · élő katalógusárak.</p></aside>
@endunless
<div class="preview-status" id="preview-status" role="status">Műsor betöltése…</div>
@php $demoConfig=['catalog_url'=>route('signage.demo',['catalog'=>1]),'program_ids'=>array_column(app(\App\Services\Signage\SignageDemoPrograms::class)->definitions(),'id'),'backgrounds'=>app(\App\Services\Signage\SignageBackgrounds::class)->options()]; @endphp
<script id="demo-config" type="application/json">@json($demoConfig)</script>
<script src="{{ url('public/signage/renderer.js') }}?v={{ filemtime(public_path('signage/renderer.js')) }}"></script>
<script src="{{ url('public/signage/demo.js') }}?v={{ filemtime(public_path('signage/demo.js')) }}"></script>
</body></html>
