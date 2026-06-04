@extends('admin.theme.default')
@section('content')
    @include('admin.breadcrumb')

    <div class="container-fluid">
        @if (@helper::checkaddons('product_import'))
            <div class="d-flex justify-content-end">
                <a href="{{ route('import') }}" class="btn btn-primary mb-2">{{ trans('labels.import') }} @if (env('Environment') == 'sendbox')
                        <small class="badge bg-danger">Addon</small>
                    @endif
                </a>
            </div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card border-0">
                    <div class="card-body">
                        <div class="table-responsive" id="table-display">
                            @include('admin.item.table')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@section('script')
    <script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/custom/additem.js') }}"></script>
    @section('script')
<script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/custom/additem.js') }}"></script>
<script>
(function () {
  const TABLE_SEL = '#itemsTable';
  const PARAM     = 'dtsearch';

  // --- helper: DataTable példányt ad vissza, akár más initelte ---
  function getDT() {
    if ($.fn.DataTable.isDataTable(TABLE_SEL)) return $(TABLE_SEL).DataTable();
    return $(TABLE_SEL).DataTable({ retrieve:true });
  }

  // --- helper: URL-ből lekérjük/beállítjuk a dtsearch paramot ---
  function getSearchFromURL() {
    const usp = new URLSearchParams(location.search);
    return usp.get(PARAM) || '';
  }
  function setSearchInURL(val) {
    const url = new URL(location.href);
    if (val) url.searchParams.set(PARAM, val);
    else     url.searchParams.delete(PARAM);
    // URL frissítés reload nélkül
    history.replaceState(null, '', url.toString());
  }

  // --- 1) Init után alkalmazzuk az URL-ben levő keresőt ---
  $(document).on('init.dt', function (e, settings) {
    if (!$(settings.nTable).is(TABLE_SEL)) return;
    const api   = new $.fn.dataTable.Api(settings);
    const saved = getSearchFromURL();
    if (saved) {
      api.search(saved).draw(false);
      const $inp = $(settings.nTableWrapper).find('.dataTables_filter input[type=search]');
      if ($inp.length) $inp.val(saved);
    }
  });

  // --- 2) Ha már inicializált volt, ugyanígy rásegítünk betöltéskor ---
  $(function () {
    if ($.fn.DataTable.isDataTable(TABLE_SEL)) {
      const api   = getDT();
      const saved = getSearchFromURL();
      if (saved) {
        api.search(saved).draw(false);
        const $inp = $(TABLE_SEL).closest('.dataTables_wrapper').find('.dataTables_filter input[type=search]');
        if ($inp.length) $inp.val(saved);
      }
    }
  });

  // --- 3) Amikor a user gépel, azonnal írjuk vissza az URL-be ---
  $(document).on('input', '.dataTables_filter input[type=search]', function () {
    setSearchInURL(this.value || '');
  });

  // --- 4) Minden gombkattintásnál mentsük a pillanatnyi keresőt az URL-be (védőháló) ---
  $(document).on('click', TABLE_SEL + ' a, ' + TABLE_SEL + ' button', function () {
    try {
      const api = getDT();
      setSearchInURL(api.search() || '');
    } catch(e) {}
  });

  // --- 5) Ha bárhol location.reload() hívódik, előtte is írjuk az URL-be az aktuális keresőt ---
  (function patchReload(){
    if (window._dtUrlReloadPatched) return;
    window._dtUrlReloadPatched = true;
    const orig = window.location.reload.bind(window.location);
    window.location.reload = function () {
      try {
        const api = getDT();
        setSearchInURL(api.search() || '');
      } catch(e) {}
      return orig();
    };
  })();

  // --- 6) Extra: ha valahol nem reload-ot, hanem átirányítást csinálsz (location.href=...),
  //              tartsuk meg a ?dtsearch=... paramot úgy, hogy hozzácsapjuk az új URL-hez.
  window.gcAppendSearchParam = function (targetUrl) {
    try {
      const current = getSearchFromURL();
      if (!current) return targetUrl;
      const url = new URL(targetUrl, location.origin);
      url.searchParams.set(PARAM, current);
      return url.toString();
    } catch(e) { return targetUrl; }
  };

})();
</script>
@endsection

@endsection
