<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ trans('labels.print') }}</title>
    <link rel="stylesheet" href="{{ url('storage/app/public/admin-assets/assets/css/bootstrap/bootstrap.min.css') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ helper::image_path(@helper::appdata()->favicon) }}">
    <style type="text/css">
/* ===== 80 mm hőnyomtató – papírtakarékos ===== */
html, body{
  width:80mm; margin:0; padding:0; background:#fff;
  font-family:system-ui,-apple-system,"Segoe UI",Arial,Helvetica,sans-serif;
  font-size:18px; line-height:1.22; font-weight:700; color:#000;
  -webkit-font-smoothing:none; text-align:center;
}
#printDiv{ margin:0 auto; }

.resept{
  width:100%; margin:0 auto; padding:1.2mm 0.8mm; background:#fff;
  page-break-inside:avoid;     /* ne törje szét a blokkot */
}
/* ha több rendelés blokkot nyomtatsz egymás után, köztük finom elválasztó */
.resept:not(:last-of-type)::after{
  content:""; display:block; border-top:1px dashed #000; margin:2mm 0 0 0;
}

/* fejlécek */
h5{ font-size:28px; margin:0; letter-spacing:1px; }
.fs-8{ font-size:22px !important; }
.fs-10, .txt-resept-font-size{ font-size:18px !important; }

/* terméknév tipó */
.product-text-size{
  font-size:20px !important; line-height:1.22; color:#000 !important; font-weight:700;
}

/* extrák feketével, tömörebben */
.product-text-size .text-muted,
.product-text-size .text-muted span{
  font-size:17px !important; font-weight:700 !important; color:#000 !important;
  opacity:1 !important; white-space:nowrap; display:inline-block;
}

/* táblázat + elválasztó minden tétel után */
.table{ width:100%; border-collapse:collapse; margin:4px 0; }
.table th, .table td{ border:0; padding:3px 1px; text-align:center; vertical-align:middle; }
.table td:nth-child(2){              /* terméknév oszlop */
  white-space:normal; text-align:left; padding-left:10mm;   /* ~1 cm balra */
}
/* VÍZSZINTES VONAL a tételek között (csak a body-ban) */
.table tbody tr{ border-bottom:1px dashed #000; }
.table tbody tr:last-child{ border-bottom:1px dashed #000; } /* az utolsó tétel után is legyen */

/* szaggatott blokk-elválasztók (összesítők köré) */
.underline-3{
  border-top:1px dashed #000; border-bottom:1px dashed #000;
  padding:3px 0; margin:5px 0;
}

/* nyomtatási optimalizáció – ne húzzon plusz papírt */
@media print{
  @page{ margin:2mm; size:auto; }
  html, body{ height:auto !important; -webkit-print-color-adjust:exact !important; }
  #btnPrint{ display:none !important; }
  #printDiv{ page-break-after:avoid !important; }
  #printDiv *:last-child{ margin-bottom:0 !important; padding-bottom:0 !important; }
}
/* rendelés-blokk: keskenyebb szélső padding */
.resept{
  width:100%;
  margin:0 auto;
  padding:1mm 0.4mm;       /* 1.2mm 0.8mm → 1mm 0.4mm */
  background:#fff;
  page-break-inside:avoid;
}

/* cellák: kicsit keskenyebb vízszintes padding */
.table th, .table td{
  border:0;
  padding:3px 0.5mm;       /* 3px 1px → 3px 0.5mm */
  text-align:center;
  vertical-align:middle;
}

/* terméknév oszlop: még balrább */
.table td:nth-child(2){
  white-space:normal;
  text-align:left;
  padding-left:6mm;        /* 10mm → 6mm (ha kell még: 5mm / 4mm) */
}


        
    </style>


</head>

<body>
    <div id="printDiv">
        <div class="resept p-2">
@php
    // Nyomtatás ideje
    $printedAt = \Carbon\Carbon::now()->timezone(config('app.timezone', 'Europe/Budapest'));

    // Alapadatok
    $transactionType = (int)($orderdata->transaction_type ?? 0);
    $orderType = (int)($orderdata->order_type ?? 0);
    $note = mb_strtoupper($orderdata->instruction ?? $orderdata->notes ?? $orderdata->order_notes ?? '');

    // Alapértelmezett címke
    $paymentLabel = 'FIZETÉS'.$transactionType;

    // 1️⃣ Készpénz
    if ($transactionType === 1 && !str_contains($note, 'KÁRTYÁVAL') ) {
        $paymentLabel = 'KÉSZPÉNZ';
    }
    // 2️⃣ Helyszíni kártyás (POS terminál)
    elseif ($transactionType === 1 && str_contains($note, 'KÁRTYÁVAL')) {
        $paymentLabel = 'KÁRTYÁS';
    }
    // 3️⃣ Online kártyás (Barion, Stripe stb.)
    elseif ($transactionType === 16 || $orderType === 16) {
        $paymentLabel = 'ONLINE KÁRTYÁS';
    }
@endphp



            <div class="address">
                <h5 class="m-0 text-uppercase fs-8 text-center line-2 fw-600">{{ @helper::appdata()->short_title }}</h5>
                <div class="col-12 mt-1 d-flex gap-1 align-items-center justify-content-center ">
                    <small class=" text-uppercase fs-10 text-center text-dark fw-500 line-2">
                        @if ($orderdata->order_type == 1)
                            {{ @$orderdata->address . ' ' . @$orderdata->landmark . ',' . @$orderdata->city . ',' . @$orderdata->state . ',' . @$orderdata->country . ',' . @$orderdata->postal_code }}
                        @elseif ($orderdata->order_type == 2)
                            {{ trans('labels.pickup') }}
                        @elseif ($orderdata->order_type == 3)
                            {{ trans('labels.pos') }}
                        @endif
                        <div class="col-12 mt-1 d-flex gap-1 align-items-center justify-content-center">
    <small class="text-uppercase fs-10 text-center text-dark fw-500 line-1">
        {{ __('Fizetés') }}: {{ $paymentLabel }}
    </small>
</div>

                    </small>
                </div>
                <div class="col-12 mt-1 d-flex gap-1 align-items-center justify-content-center">
                    <p class=" m-0 fw-500 text-uppercase fs-10 text-center text-dark line-1">
                        {{ trans('labels.name') }} :</p>
                    <small class="fw-500 text-uppercase fs-10 text-center text-dark  line-1">
                        {{ @$orderdata->name }}
                    </small>
                </div>
                <div class="col-12 mt-1 d-flex gap-1 align-items-center justify-content-center">
                    <p class="fw-500 m-0 text-uppercase fs-10 text-center text-dark line-1">
                        {{ trans('labels.email') }} :</p>
                    <small class="fw-500 text-uppercase fs-10 text-center text-dark  line-1">
                        {{ @$orderdata->email }}
                    </small>
                </div>
                <div class="col-12 mt-1 d-flex gap-1 align-items-center justify-content-center">
                    <p class="fw-500 m-0 text-uppercase fs-10 text-center text-dark line-1">
                        {{ trans('labels.mobile') }} :</p>
                    <small class="fw-500 text-uppercase fs-10 text-center text-dark  line-1">
                        {{ @$orderdata->mobile }}
                    </small>
                </div>
            </div>
            <div class="total-billes-amount">
                <div
                    class="fw-500 d-flex gap-1 align-items-center justify-content-center mt-1 text-uppercase fs-10 text-center text-dark">
                    {{ trans('labels.order_number') }} :
                    <small class="fw-500 text-uppercase fs-10 text-center text-dark line-1">
                        #{{ $orderdata->order_number }}
                    </small>
                </div>
                <p
                    class="fw-500 d-flex gap-1 align-items-center justify-content-center m-0 text-uppercase fs-10 text-center text-dark line-1">
                    {{ trans('labels.order_date') }} :
                    <small class="fw-500 text-uppercase fs-10 text-center text-dark line-1">
    {{ ($orderdata->created_at) }}
</small>

                </p>
            </div>
            <div class="total-billes-amount">
                @if ($orderdata->delivery_date != '')
                    <div
                        class="fw-500 d-flex gap-1 align-items-center justify-content-center m-0 text-uppercase fs-10 text-center text-dark">
                        {{ $orderdata->order_type == '1' ? trans('labels.delivery_date') : trans('labels.pickup_date') }}
                        :
                       <small class="fw-500 text-uppercase fs-10 text-center text-dark line-1">
    {{($orderdata->created_at) }} 
</small>


                    </div>
                @endif
                @if ($orderdata->delivery_time != '')
                    <p
                        class="fw-500 d-flex gap-1 align-items-center justify-content-center m-0 text-uppercase fs-10 text-center text-dark line-1">
                        {{ $orderdata->order_type == '1' ? trans('labels.delivery_time') : trans('labels.pickup_time') }}
                        :
                        <small
                            class="fw-500 text-uppercase fs-10 text-center text-dark line-1">{{ $orderdata->delivery_time }}
                        </small>
                    </p>
                @endif
            </div>
            <table class="table table-borderless my-2 bg-transparent">
               <thead class="underline-3">
  <tr class="text-dark">
    <th scope="col" class="product-text-size fw-bold">#</th>
    <th scope="col" class="product-text-size fw-bold">{{ trans('labels.item') }}</th>
    <th scope="col" class="product-text-size fw-bold text-center">db</th>
    <th scope="col" class="product-text-size fw-bold text-center">{{ trans('labels.price') }}</th>
  </tr>
</thead>


                <tbody>
                    @php
                        $order_total = 0;
                        $qty = 0;
                    @endphp
                    @foreach ($ordersdetails as $key => $orders)
                        @php
                            $order_total +=
                                ($orders['item_price'] +
                                    $orders['addons_total_price'] +
                                    $orders['extras_total_price']) *
                                $orders['qty'];
                            $qty += $orders['qty'];
                        @endphp
                       <tr class="align-middle">
  <td class="py-2">
    <p class="fw-500 text-dark line-1 m-0 product-text-size">{{ ++$key }}</p>
  </td>

  <td class="py-2">
    <h6 class="m-0 fw-500 product-text-size">
      {{ $orders->item_name }}<br>
      @php
          $addons_name = explode('| ', $orders->addons_name);
          $extras_name = explode('| ', $orders->extras_name);
      @endphp
      @if ($orders->addons_id != '')
        @foreach ($addons_name as $key => $val)
          <span class="text-muted">{{ trim($addons_name[$key]) }}</span><br>
        @endforeach
      @endif
      @if ($orders->extras_id != '')
        @foreach ($extras_name as $key => $val)
          <span class="text-muted">{{ trim($extras_name[$key]) }}</span><br>
        @endforeach
      @endif
    </h6>
  </td>

  <!-- 3. oszlop: DB (mennyiség) -->
  <td class="py-2 text-end">
    <p class="m-0 text-dark product-text-size">{{ $orders->qty }}</p>
  </td>

  <!-- 4. oszlop: ÁR (egységár + extrák összege) -->
  <td class="py-2 text-end">
    <p class="m-0 text-dark product-text-size">
      {{ helper::currency_format($orders->item_price) }}
      @if ($orders->addons_total_price != 0 || $orders->extras_total_price != 0)
        <br><small class="text-muted">+
          {{ helper::currency_format($orders->addons_total_price + $orders->extras_total_price) }}</small>
      @endif
    </p>
  </td>
</tr>


                    @endforeach
                </tbody>
             <tfoot>
  <tr class="underline-3">
    <td class="py-2" colspan="2">
      <h6 class="line-1 m-0 fw-600 product-text-size">{{ trans('labels.subtotal') }}</h6>
    </td>
    <td class="py-2 text-end">
      <p class="m-0 text-dark product-text-size">{{ $qty }}</p>
    </td>
    <td class="py-2 text-end">
      <p class="m-0 text-dark product-text-size">{{ helper::currency_format($order_total) }}</p>
    </td>
  </tr>
</tfoot>

            </table>
            <div class="col-12 d-flex mb-2 justify-content-end">
                <div class="col-7">
                    <div class="col-12">
                        <div class="text-dark">
                            @if (!empty($orderdata->discount_amount))
                                <div class="d-flex justify-content-between text-dark my-1">
                                    <div class="">
                                        <span class="txt-resept-font-size fw-500 text-uppercase line-1">
                                            {{ trans('labels.discount') }}
                                            {{ $orderdata->offer_code != '' ? '(' . $orderdata->offer_code . ')' : '' }}
                                        </span>
                                    </div>
                                    <div class="">
                                        <span class="txt-resept-font-size fw-500 text-uppercase text-end line-1">
                                            {{ helper::currency_format($orderdata->discount_amount) }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                            @php
                                $tax = explode('|', $orderdata->tax_amount);
                                $tax_name = explode('|', $orderdata->tax_name);
                            @endphp
                            @if ($orderdata->tax_amount != null && $orderdata->tax_name != null)
                                @foreach ($tax as $key => $tax_value)
                                    <div class="d-flex justify-content-between text-dark my-1">
                                        <div class="">
                                            <span
                                                class="txt-resept-font-size fw-500 text-uppercase line-1">{{ $tax_name[$key] }}</span>
                                        </div>
                                        <div class="">
                                            <span class="txt-resept-font-size fw-500 text-uppercase text-end line-1">
                                                {{ helper::currency_format($tax_value) }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            @if ($orderdata->delivery_charge != 0)
                                <div class="d-flex justify-content-between text-dark my-1">
                                    <div class="">
                                        <span class="txt-resept-font-size fw-500 text-uppercase line-1">
                                            {{ trans('labels.delivery_charge') }}
                                        </span>
                                    </div>
                                    <div class="">
                                        <span class="txt-resept-font-size fw-500 text-uppercase line-1 text-end">
                                            {{ helper::currency_format($orderdata->delivery_charge) }}
                                        </span>
                                    </div>
                                </div>
                            @endif


                                {{-- Megjegyzés / Customer note --}}
                                @php
                                    $order_note = $orderdata->instruction ?? $orderdata->notes ?? $orderdata->order_notes ?? '';
                                @endphp
                                @if(!empty($order_note))
                                    <div class="underline-3 note-box">
                                        <div class="note-title">{{ trans('labels.note') }}</div>
                                        <div class="note-text">{{ $order_note }}</div>
                                    </div>
                                @endif

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-between underline-3 py-2">
                <span class="fw-semibold product-text-size line-1">{{ trans('labels.grand_total') }}</span>
                <span
                    class="fw-semibold line-1 product-text-size">{{ helper::currency_format($orderdata->grand_total) }}</span>
            </div>
            <h2 class="my-2 fs-8 fw-600 text-center line-1">{{ trans('labels.thanks_for_order') }}</h2>
            <div class="col-12 mt-2 d-flex justify-content-center">
                <button type='button' id="btnPrint"
                    class="rounded border-0 btn btn-primary text-light text-capitalize fs-8 px-3 py-2">{{ trans('labels.print') }}</button>
            </div>
        </div>
    </div>
    <script>
        const $btnPrint = document.querySelector("#btnPrint");
        $btnPrint.addEventListener("click", () => {
            window.print();
        });
    </script>
</body>
