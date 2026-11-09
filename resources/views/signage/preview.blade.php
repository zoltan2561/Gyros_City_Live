<!doctype html>
<html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gyros City · TV reklámműsor</title>
<link rel="stylesheet" href="{{ url('public/signage/player.css') }}?v=2"></head>
<body class="{{ request()->boolean('fullscreen')?'clean-preview':'' }}"><main id="signage-stage" class="signage-stage"></main>
@unless(request()->boolean('fullscreen'))
<aside class="preview-controls" aria-label="Reklámműsor beállításai">
<label for="preview-program">Műsor</label><select id="preview-program"><option value="all">A három videómásolat</option><option value="burger">Burger videómásolat</option><option value="menu">Étlap videómásolat</option><option value="pizza">Pizza videómásolat</option></select>
<button type="button" id="preview-pause" aria-pressed="false">Szünet</button>
<button type="button" id="preview-next">Következő jelenet</button>
<button type="button" id="preview-fullscreen">Teljes képernyő</button>
<p>Helyi videómásolatok · élő katalógusárakkal.</p></aside>
@endunless
<div class="preview-status" id="preview-status" role="status">Műsor betöltése…</div>
@php $demoConfig=['catalog_url'=>route('signage.demo',['catalog'=>1])]; @endphp
<script id="demo-config" type="application/json">@json($demoConfig)</script>
<script src="{{ url('public/signage/renderer.js') }}?v=2"></script>
<script src="{{ url('public/signage/demo.js') }}?v=2"></script>
</body></html>
