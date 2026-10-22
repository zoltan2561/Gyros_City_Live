@php
    $orderStatusName = helper::gettype($orderdata->status, $orderdata->status_type, $orderdata->order_type)->name ?? 'Állapot';
    $customerData = [$orderdata->order_number, $orderdata->name, $orderdata->mobile, $orderdata->email,
        $orderdata->address, $orderdata->city, $orderdata->state, $orderdata->country, $orderdata->landmark, $orderdata->postal_code];
@endphp
<div class="counter-toolbar">
    <div><h4>{{ $orderdata->order_number }}</h4><small>{{ helper::date_format($orderdata->created_at) }} · {{ $orderdata->created_at->format('H:i') }}</small></div>
    <div class="counter-toolbar-actions">
        <a class="btn btn-outline-secondary" href="{{ url('admin/orders') }}">← Rendelések</a>
        <a class="btn btn-outline-secondary" href="{{ url('admin/print/'.$orderdata->id) }}">Nyomtatás</a>
        @if ($getdriver->count() && (int) $orderdata->order_type === 1 && in_array((int) $orderdata->status_type, [1, 2], true))
            <label class="visually-hidden" for="driver">Futár</label>
            <select class="form-select" name="driver" id="driver" style="width:180px">
                <option value="0">Futár kiválasztása</option>
                @foreach ($getdriver as $driver)
                    <option value="{{ $driver->id }}" @selected($orderdata->driver_id == $driver->id)>{{ $driver->name }}</option>
                @endforeach
            </select>
        @endif
        @include('admin.orders.status-control', ['statusOptions' => helper::customstauts($orderdata->order_type)])
    </div>
</div>
<div class="counter-summary">
    <section class="card">
        <div class="card-header bg-transparent"><h6>Átvétel és fizetés</h6></div>
        <div class="card-body">
            <p class="counter-primary">{{ (int) $orderdata->order_type === 1 ? 'Kiszállítás' : ((int) $orderdata->order_type === 2 ? 'Elvitel' : 'Helyszíni rendelés') }} · {{ helper::currency_format($orderdata->grand_total) }}</p>
            <p>{{ (int) $orderdata->order_type === 3 ? ((int) $orderdata->transaction_type === 1 ? trans('labels.cash') : trans('labels.online')) : $orderPaymentName }}</p>
            <div class="counter-meta">
                <strong class="{{ (int) $orderdata->payment_status === 1 ? 'text-danger' : 'text-success' }}">{{ (int) $orderdata->payment_status === 1 ? 'Nincs fizetve' : 'Fizetve' }}</strong>
                @if ($orderdata->delivery_date)<span>{{ helper::date_format($orderdata->delivery_date) }}</span>@endif
                @if ($orderdata->delivery_time)<span>{{ $orderdata->delivery_time }}</span>@endif
            </div>
            @if ($orderdata->transaction_id && in_array($orderdata->transaction_type, [3,4,5,6,7,8,9,10]))
                <small>Tranzakció: {{ $orderdata->transaction_id }}</small>
            @endif
        </div>
    </section>
    <section class="card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center"><h6>Vásárló</h6>
            <button type="button" class="counter-edit" aria-label="Vásárló szerkesztése" data-edit-customer="customer_info" data-customer="{{ json_encode($customerData) }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
        </div>
        <div class="card-body">
            <p class="counter-primary">{{ $orderdata->name }}</p>
            <p><a href="tel:{{ preg_replace('/[^+0-9]/', '', $orderdata->mobile ?? '') }}">{{ $orderdata->mobile }}</a></p>
            @if ($orderdata->email)<p><small>{{ $orderdata->email }}</small></p>@endif
        </div>
    </section>
    <section class="card">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center"><h6>{{ (int) $orderdata->order_type === 1 ? 'Szállítási cím' : 'Átvétel' }}</h6>
            @if ($orderdata->order_from !== 'pos' && (int) $orderdata->order_type === 1)
                <button type="button" class="counter-edit" aria-label="Cím szerkesztése" data-edit-customer="bill_info" data-customer="{{ json_encode($customerData) }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
            @endif
        </div>
        <div class="card-body">
            @if ((int) $orderdata->order_type === 1)
                <p class="counter-primary">{{ $orderdata->city }}</p><p>{{ $orderdata->address }}</p>
                @if ($orderdata->driver_info)<p><small>Futár: {{ $orderdata->driver_info->name }}</small></p>@endif
            @else
                <p class="counter-primary">{{ (int) $orderdata->order_type === 2 ? 'Személyes átvétel' : 'Helyszíni rendelés' }}</p>
            @endif
        </div>
    </section>
    <section class="counter-notes">
        <p class="customer-note"><strong>Vásárló megjegyzése:</strong> {{ $orderdata->order_notes ?: 'Nincs megjegyzés.' }}</p>
        <form action="{{ url('admin/orders/order_note') }}" method="POST">
            @csrf
            <input type="hidden" name="order_id" value="{{ $orderdata->order_number }}">
            <label for="counter-note">Belső megjegyzés</label>
            <input type="text" id="counter-note" name="admin_notes" class="form-control" value="{{ $orderdata->admin_notes }}" required>
            <button type="submit" class="btn btn-primary">Mentés</button>
        </form>
    </section>
</div>
