@if (in_array((int) $orderdata->status_type, [1, 2], true))
    <div class="order-status-control">
        <label class="visually-hidden" for="order-status-{{ $orderdata->id }}">{{ $orderdata->order_number }} állapota</label>
        <select id="order-status-{{ $orderdata->id }}" class="form-select order-status-select"
            data-order-id="{{ $orderdata->id }}" data-current="{{ $orderdata->status }}"
            data-url="{{ url('admin/orders/update') }}">
            <option value="{{ $orderdata->status }}" selected>{{ $orderStatusName }}</option>
            @foreach ($statusOptions as $status)
                @if ((int) $status->id !== (int) $orderdata->status)
                    <option value="{{ $status->id }}" data-type="{{ $status->type }}">{{ $status->name }}</option>
                @endif
            @endforeach
        </select>
        <small class="order-status-feedback" role="status" aria-live="polite"></small>
    </div>
@else
    <span class="order-status-final {{ (int) $orderdata->status_type === 4 ? 'text-order-cancelled' : 'text-order-completed' }}">{{ $orderStatusName }}</span>
@endif
