@extends('admin.theme.default')
@section('styles')
    <link rel="stylesheet" href="{{ url('public/city/admin-orders.css') }}?v={{ filemtime(public_path('city/admin-orders.css')) }}">
@endsection
@section('content')
    @include('admin.breadcrumb')
    <div class="container-fluid counter-order">
        @include('admin.orders.counter-summary')
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 mt-3 counter-items-card">
                    <div class="card-header d-flex align-items-center bg-transparent text-dark py-3">
                        <i class="fa-solid fa-bag-shopping fs-5"></i>
                        <h6 class="px-2 fw-500">Rendelt tételek <span class="counter-item-count">{{ $ordersdetails->sum('qty') }} db</span></h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table counter-items">
                                <thead>
                                    <tr>
                                        <th>{{ trans('labels.image') }}</th>
                                        <th>{{ trans('labels.qty') }}</th>
                                        <th>{{ trans('labels.item') }}</th>
                                        <th class="text-end">{{ trans('labels.unit_cost') }}</th>
                                        <th class="text-end">{{ trans('labels.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $data = [];
                                        $order_total = 0;
                                    @endphp
                                    @foreach ($ordersdetails as $orders)
                                        @php
                                            $total_price =
                                                ($orders['item_price'] +
                                                    $orders['addons_total_price'] +
                                                    $orders['extras_total_price']) *
                                                $orders['qty'];
                                            $data[] = ['total_price' => $total_price];
                                            $order_total = array_sum(array_column(@$data, 'total_price'));
                                            $addonstotal = $orders->addons_total_price + $orders->extras_total_price;
                                        @endphp
                                        <tr>
                                            <td><img src="{{ helper::image_path($orders->item_image) }}" class="order-item-photo" alt=""></td>
                                            <td class="order-item-qty"><span class="order-qty">{{ $orders->qty }}<small> db</small></span></td>
                                            <td class="order-product">
                                                <div class="order-item-name">{{ $orders->item_name }}</div>
                                                @include('admin.orders.item-customizations')
                                            </td>
                                            <td class="text-end order-unit-price">
                                                {{ helper::currency_format($orders->item_price) }}
                                                @if ($addonstotal != 0)
                                                    <br><small class="text-muted">+
                                                        {{ helper::currency_format($addonstotal) }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end order-line-total">
                                                {{ helper::currency_format($total_price) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="counter-totals">
                            <table class="table counter-totals-table" aria-label="Összesítés">
                                <tbody>
                                    <tr class="order-total-row">
                                        <td class="text-end">
                                            <p class="fw-400">{{ trans('labels.subtotal') }}</p>
                                        </td>
                                        <td class="text-end">
                                            <p class="fw-400">{{ helper::currency_format($order_total) }}</p>
                                        </td>
                                    </tr>
                                    @if ($orderdata->discount_amount > 0)
                                        <tr class="order-total-row">
                                            <td class="text-end">
                                                <p class="fw-400">{{ trans('labels.discount') }}</p>
                                                {{ $orderdata->offer_code != '' ? '(' . $orderdata->offer_code . ')' : '' }}
                                            </td>
                                            <td class="text-end">
                                                <p class="fw-400">-
                                                    {{ helper::currency_format($orderdata->discount_amount) }}</p>
                                            </td>
                                        </tr>
                                    @endif
                                    @php
                                        $tax = explode('|', $orderdata->tax_amount);
                                        $tax_name = explode('|', $orderdata->tax_name);
                                    @endphp
                                    @if ($orderdata->tax_amount != null && $orderdata->tax_amount != '')
                                        @foreach ($tax as $key => $tax_value)
                                            <tr class="order-total-row">
                                                <td class="text-end">
                                                    <p class="fw-400">{{ $tax_name[$key] }}</p>
                                                </td>
                                                <td class="text-end">
                                                    <p class="fw-400">{{ helper::currency_format($tax_value) }}</p>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                    @if ($orderdata->delivery_charge > 0)
                                        <tr class="order-total-row">
                                            <td class="text-end">
                                                <p class="fw-400">{{ trans('labels.delivery_charge') }}</p>
                                            </td>
                                            <td class="text-end">
                                                <p class="fw-400">{{ helper::currency_format($orderdata->delivery_charge) }}</p>
                                            </td>
                                        </tr>
                                    @endif
                                    <tr class="order-total-row order-grand-total">
                                        <td class="text-end">
                                            <strong>{{ trans('labels.grand_total') }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ helper::currency_format($orderdata->grand_total) }}</strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Customer and Bill info Modal-->
        <div class="modal fade" id="customerinfo" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header justify-content-between">
                        <h5 class="modal-title" id="modalbankdetailsLabel">{{ trans('labels.edit') }}</h5>
                        <button type="button" class="btn-close m-0" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form enctype="multipart/form-data" action="{{ URL::to('admin/orders/customerbillinfo') }}"
                        method="POST">
                        <div class="modal-body">
                            @csrf
                            <input type="hidden" name="order_id" id="modal_order_id" class="form-control"
                                value="">
                            <input type="hidden" name="edit_type" id="edit_type" class="form-control" value="">
                            <div id="customer_info">
                                <div class="form-group col-md-12">
                                    <label for="user_name"> {{ trans('labels.name') }} </label>
                                    <div class="controls">
                                        <input type="text" name="user_name" id="user_name" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="user_mobile"> {{ trans('labels.mobile') }} </label>
                                    <div class="controls">
                                        <input type="text" name="user_mobile" id="user_mobile" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="user_email"> {{ trans('labels.email') }} </label>
                                    <div class="controls">
                                        <input type="text" name="user_email" id="user_email" class="form-control"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div id="bill_info">
                                <div class="form-group col-md-12">
                                    <label for="bill_address"> {{ trans('labels.address') }} </label>
                                    <div class="controls">
                                        <input type="text" name="bill_address" id="bill_address" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="bill_city"> {{ trans('labels.city') }} </label>
                                    <div class="controls">
                                        <input type="text" name="bill_city" id="bill_city" class="form-control"
                                            required>
                                    </div>
                                </div>
                                <input type="hidden" name="bill_state" id="bill_state">
                                <input type="hidden" name="bill_country" id="bill_country">
                                <input type="hidden" name="bill_landmark" id="bill_landmark">
                                <input type="hidden" name="bill_pincode" id="bill_pincode">
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger"
                                data-bs-dismiss="modal">{{ trans('labels.close') }}</button>
                            <button
                                @if (env('Environment') == 'sendbox') type="button" onclick="myFunction()" type="submit" @endif
                                class="btn btn-primary"> {{ trans('labels.save') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
@section('script')
    <script src="{{ url('public/city/admin-orders.js') }}?v={{ filemtime(public_path('city/admin-orders.js')) }}"></script>
    <script src="{{ url(env('ASSETSPATHURL') . 'admin-assets/assets/js/custom/orders.js') }}"></script>
    <script>
        document.querySelectorAll('[data-edit-customer]').forEach(button => {
            button.addEventListener('click', () => editcustomerdata(...JSON.parse(button.dataset.customer), button.dataset.editCustomer));
        });

        function editcustomerdata(order_id, customer_name, customer_mobile, customer_email, bill_address, bill_city,
            bill_state, bill_country, bill_landmark, bill_pincode, type) {
            "use strict";
            $('#modal_order_id').val(order_id);
            if (type == "customer_info") {
                $('#user_name').val(customer_name);
                $('#user_mobile').val(customer_mobile);
                $('#user_email').val(customer_email);
                $('#edit_type').val(type);
                $('#bill_info').hide();
                $('#customer_info').show();
                $('#bill_address').removeAttr('required');
                $('#bill_city').removeAttr('required');
                $('#bill_state').removeAttr('required');
                $('#bill_country').removeAttr('required');
                $('#bill_landmark').removeAttr('required');
                $('#bill_pincode').removeAttr('required');
            } else {
                $('#bill_address').val((bill_address || '').replace(/[|]+/g, ","));
                $('#bill_city').val(bill_city);
                $('#bill_state').val(bill_state);
                $('#bill_country').val(bill_country);
                $('#bill_landmark').val(bill_landmark);
                $('#bill_pincode').val(bill_pincode);
                $('#edit_type').val(type);
                $('#bill_info').show();
                $('#customer_info').hide();
                $('#user_name').removeAttr('required');
                $('#user_email').removeAttr('required');
                $('#user_mobile').removeAttr('required');
            }
            $('#customer_info input').prop('required', type === 'customer_info');
            $('#bill_address, #bill_city').prop('required', type === 'bill_info');
            $('#customerinfo').modal('show');

        }

        $('#driver').on('change', function() {
            $.ajax({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                url: "{{ URL::to('admin/orders/assign-driver') }}",
                type: "post",
                dataType: "json",
                data: {
                    driver_id: $(this).val(),
                    order_id: "{{ $orderdata->id }}",
                },
                success: function(response) {
                    $("#preload").hide();
                    if (response.status == 0) {
                        toastr.error(response.message);
                    } else {
                        location.reload();
                    }
                }
            });
        });
    </script>
@endsection
