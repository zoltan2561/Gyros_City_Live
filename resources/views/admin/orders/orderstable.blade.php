<table class="table table-striped table-bordered zero-configuration">
    <thead>
        <tr>
            <th>#</th>
            <th>{{ trans('labels.order_number') }}</th>
            <th>{{ trans('labels.date') }}</th>
            <th>{{ trans('labels.user_info') }}</th>
            <th>{{ trans('labels.order_type') }}</th>
            <th>{{ trans('labels.payment_type') }}</th>
            <th>{{ trans('labels.grand_total') }}</th>
            <th>{{ trans('labels.status') }}</th>
            <th>{{ trans('labels.action') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($getorders as $key => $orderdata)
            <tr id="dataid{{ $orderdata->id }}">
                <td>{{ ++$key }}</td>
                <td>
                    <div class="d-flex justify-content-between">
                        <a href="{{ URL::to('admin/invoice/' . $orderdata->id) }}"
                            class="text-dark">{{ $orderdata->order_number }}</a>
                        @if ($orderdata->admin_notes != null)
                            <a href="javascript:void(0)" class="btn btn-primary btn-sm hov square"
                                tooltip="{{ $orderdata->admin_notes }}">
                                <i class="fa-solid fa-clipboard"></i>
                            </a>
                        @endif
                    </div>
                </td>
                <td>{{ $orderdata->created_at }}</td>
                <td>
                    {{ @$orderdata->name }}
                </td>
                <td>
                    @if ($orderdata->order_type == 1)
                        {{ trans('labels.delivery') }}
                    @elseif ($orderdata->order_type == 2)
                        {{ trans('labels.pickup') }}
                    @elseif ($orderdata->order_type == 3)
                        {{ trans('labels.pos') }}
                    @endif
                </td>
                <td>
                    @if ($orderdata->order_type == 3)
                        @if ($orderdata->transaction_type == 0)
                            {{ trans('labels.online') }}
                        @elseif ($orderdata->transaction_type == 1)
                            {{ trans('labels.cash') }}
                        @endif
                    @else
                        {{ helper::getpayment($orderdata->transaction_type) }}
                    @endif
                    <br>
                    @if ($orderdata->payment_status == 1)
                        <small class="text-danger"> <i class="fa-regular fa-clock"></i>
                            {{ trans('labels.unpaid') }}</small>
                    @else
                        <small class="text-success"> <i class="fa-regular fa-check"></i>
                            {{ trans('labels.paid') }}</small>
                    @endif
                </td>
                <td>{{ helper::currency_format($orderdata->grand_total) }}</td>
                <td>
                    @if ($orderdata->status_type == 1)
                        <small
                            class="text-order-placed">{{ helper::gettype($orderdata->status, $orderdata->status_type, $orderdata->order_type)->name }}</small>
                    @elseif ($orderdata->status_type == 2)
                        <small
                            class="text-order-waitingpickup">{{ helper::gettype($orderdata->status, $orderdata->status_type, $orderdata->order_type)->name }}</small>
                    @elseif ($orderdata->status_type == 3)
                        <small
                            class="text-order-completed">{{ helper::gettype($orderdata->status, $orderdata->status_type, $orderdata->order_type)->name }}</small>
                    @elseif ($orderdata->status_type == 4)
                        <small
                            class="text-order-cancelled">{{ helper::gettype($orderdata->status, $orderdata->status_type, $orderdata->order_type)->name }}</small>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <a class="btn btn-sm btn-secondary square" tooltip="View" title="{{ trans('labels.view') }}"
                            href="{{ URL::to('admin/invoice/' . $orderdata->id) }}"><i
                                class="fa-regular fa-eye"></i></a>
                        <a class="btn btn-sm btn-primary square" tooltip="Print" title="{{ trans('labels.print') }}"
                            href="{{ URL::to('admin/print/' . $orderdata->id) }}"><i
                                class="fa-regular fa-print"></i></a>
                        <a href="{{ URL::to('admin/generatepdf/' . $orderdata->id) }}" class="btn btn-warning square"
                            tooltip="Download PDF">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                        </a>
                        @if ($orderdata->transaction_type == 1 && $orderdata->payment_status == 1 && $orderdata->status_type == 3)
                            <a class="btn btn-sm btn-info square" tooltip="Payment Status"
                                onclick="codpayment('{{ $orderdata->order_number }}','{{ $orderdata->grand_total }}')"><i
                                    class="fa-solid fa-file-invoice-dollar"></i>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
