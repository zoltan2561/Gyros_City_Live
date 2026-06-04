<!DOCTYPE html>
<html lang="en" dir="{{ session('direction') == 2 ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ @helper::appdata()->title }} | {{ trans('labels.admin_title') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ helper::image_path(@helper::appdata()->favicon) }}">
    <link rel="stylesheet"
          href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/bootstrap/bootstrap.min.css') }}">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/fontawesome/all.min.css') }}">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/toastr/toastr.min.css') }}">
    <!-- Toastr CSS -->
    <link rel="stylesheet"
          href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/sweetalert/sweetalert2.min.css') }}">
    <!-- Sweetalert CSS -->
    <link rel="stylesheet" href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/style.css') }}">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/responsive.css') }}">
    <!-- Responsive CSS -->
    <link rel="stylesheet"
          href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/datatables/dataTables.bootstrap5.min.css') }}">
    <!-- dataTables css -->
    <link rel="stylesheet"
          href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/datatables/dataTables.bootstrap5.min.css') }}">
    <!-- dataTables css -->
    <link rel="stylesheet"
          href="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/css/datatables/buttons.dataTables.min.css') }}">
    <!-- dataTables css -->
    <style>
        :root {
            --bs-primary: {{ @helper::appdata()->admin_primary_color != null ? @helper::appdata()->admin_primary_color : '#01112B' }};
            --bs-secondary: {{ @helper::appdata()->admin_secondary_color != null ? @helper::appdata()->admin_secondary_color : '#0a98af' }};
        }
    </style>
    @yield('styles')
</head>

<body>
{{-- @include('admin.theme.preloader') --}}
<main>
    <div class="wrapper">
        @include('admin.theme.header')
        <div class="content-wrapper">
            @include('admin.theme.sidebar')
            <div class="{{ session()->get('direction') == 2 ? 'main-content-rtl' : 'main-content' }}">
                <div class="page-content">
                    @if (helper::check_alert() == 0)
                        <div class="alert alert-danger text-center">
                            <a href="{{ URL::to('admin/settings') }}" class="text-dark"> <i class="fa fa-cog"></i>
                                {{ trans('messages.settings_note') }}</a>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </div>
        </div>
        <!--Modal: order-modal-->
        <div class="modal fade" id="order-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <div class="modal-dialog modal-notify modal-info" role="document">
                <div class="modal-content text-center">
                    <div class="modal-header d-flex justify-content-center">
                        <p class="heading">{{ trans('messages.be_up_to_date') }}</p>
                    </div>
                    <div class="modal-body"><i class="fa fa-bell fa-4x animated rotateIn mb-4"></i>
                        <p>{{ trans('messages.new_order_arrive') }}</p>
                    </div>
                    <div class="modal-footer flex-center">
                        <a role="button" class="btn btn-outline-secondary-modal btn-primary waves-effect"
                           onclick="window.location.reload();"
                           data-bs-dismiss="modal">{{ trans('labels.okay') }}</a>
                    </div>
                </div>
            </div>
        </div>
        <!--Modal: modalPush-->
        <!-- ASSIGN-DRIVER-MODAL-START -->
        <div id="myModal" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button"
                                class="btn-close {{ session()->get('direction') == 2 ? 'close' : '' }}"
                                data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" id="assign">
                        @csrf
                        <input type="hidden" name="driverurl" id="driverurl"
                               value="{{ URL::to('admin/orders/assign-driver') }}">
                        <div class="modal-body">
                            <input type="hidden" class="form-control" id="order_id" name="order_id" readonly>
                            <div class="form-group">
                                <label for="category_id"
                                       class="col-form-label">{{ trans('labels.order_number') }}</label>
                                <input type="text" class="form-control" id="order_number" readonly="">
                                <span class="id_error text-danger"></span>
                            </div>
                            <div class="form-group">
                                <label for="category_id"
                                       class="col-form-label">{{ trans('messages.select_driver') }}
                                </label>
                                <select class="form-control" name="driver_id" id="driver_id" required="required">
                                    <option value="" selected>{{ trans('messages.select_driver') }}
                                    </option>
                                    @if (is_array(@$getdriver) || is_object(@$getdriver))
                                        @foreach (@$getdriver as $driver)
                                            <option value="{{ $driver->id }}">
                                                {{ $driver->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <span class="driver_error text-danger"></span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger"
                                    data-bs-dismiss="modal">{{ trans('labels.close') }}</button>
                            <button type="button" class="btn btn-primary"
                                    onclick="assigndriver()">{{ trans('labels.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- ASSIGN-DRIVER-MODAL-END -->
        <footer class="py-3 text-center bg-white fixed-bottom border-top">{{ helper::appdata()->copyright }}
        </footer>
    </div>
</main>
@include('admin.theme.script')
@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script>
            toastr.error('{{ $error }}');
        </script>
    @endforeach
@endif
<script type="text/javascript">
    let are_you_sure = "{{ trans('messages.are_you_sure') }}";
    let yes = "{{ trans('messages.yes') }}";
    let no = "{{ trans('messages.no') }}";
    let wrong = "{{ trans('messages.wrong') }}";
    let cannot_delete = "{{ trans('messages.cannot_delete') }}";
    let last_image = "{{ trans('messages.last_image') }}";
    let record_safe = "{{ trans('messages.record_safe') }}";
    let select = "{{ trans('labels.select') }}";
    let variation = "{{ trans('labels.variation') }}";
    let enter_variation = "{{ trans('labels.variation') }}";
    let product_price = "{{ trans('labels.product_price') }}";
    let enter_product_price = "{{ trans('labels.product_price') }}";
    let sale_price = "{{ trans('labels.sale_price') }}";
    let enter_sale_price = "{{ trans('labels.sale_price') }}";

    function currency_format(price) {
        if ("{{ @helper::appdata()->currency_position }}" == 1) {
            return "{{ @helper::appdata()->currency }}" + parseFloat(price).toFixed(2);
        } else {
            return parseFloat(price).toFixed(2) + "{{ @helper::appdata()->currency }}";
        }
    }
    toastr.options = {
        "closeButton": true,
        "progressBar": true
    }
    @if (Session::has('success'))
    toastr.success("{{ session('success') }}");
    @endif
    @if (Session::has('error'))
    toastr.error("{{ session('error') }}");
    @endif

</script>
<script type="text/javascript">
  // New Notification + 2 percenként ismételt hang
var noticount = 0;
var reminderTimer = null;
var reminderAudio = null;

(function noti() {
  $.ajax({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    url: "{{ url('admin/getorder') }}",
    method: 'GET',
    dataType: "json",
    success: function (response) {
      noticount = localStorage.getItem("count");

      // jelvény
      if (response.count > 9) {
        $('#notificationcount').text(response.count + "+");
      } else {
        $('#notificationcount').text(response.count);
      }

      if (response.count != 0) {
        if (noticount != response.count) {
          // új rendelés
          localStorage.setItem("count", response.count);
          jQuery("#order-modal").modal('show');

          // új hang
          var soundUrl = "{{ url(env('ASSETSPATHURL')) }}/admin-assets/notification/" + response.noti;
          if (!reminderAudio || reminderAudio.src !== soundUrl) {
            reminderAudio = new Audio(soundUrl);
            reminderAudio.preload = 'auto';
            reminderAudio.muted = false;
            reminderAudio.volume = 1.0;
            reminderAudio.playsInline = true;
          }

          // azonnali lejátszás
          reminderAudio.currentTime = 0;
          reminderAudio.play().catch(() => {});

          // ismétlés 2 percenként, amíg nyitva van a modál
          if (reminderTimer) clearInterval(reminderTimer);
          reminderTimer = setInterval(function () {
            if ($('#order-modal').is(':visible')) {
              try {
                reminderAudio.currentTime = 0;
                reminderAudio.play().catch(() => {});
              } catch (e) {}
            } else {
              clearInterval(reminderTimer);
              reminderTimer = null;
            }
          }, 120000); // 2 perc = 120000 ms
        }
      } else {
        localStorage.setItem("count", response.count);
      }

      setTimeout(noti, 5000); // 5 mp-enként lekérdezés
    }
  });
})();

</script>





<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/common.js') }}"></script><!-- Common JS -->
@yield('script')
</body>

</html>
