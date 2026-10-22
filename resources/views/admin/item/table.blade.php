<table id="itemsTable" class="table products-table">
    <thead><tr><th>Sorrend</th><th>Termék</th><th>Kategória</th><th>Ár</th><th>Elérhetőség</th><th>Ma nem elérhető</th><th>Mai ajánlat</th><th>Műveletek</th><th>Módosítva</th></tr></thead>
    <tbody id="productRows">
    @foreach ($getitem as $item)
        @php
            $state = $item->today_unavailable ? 'today' : ((int) $item->item_status === 1 ? 'active' : 'paused');
            $category = $categories->firstWhere('id', $item->cat_id);
            $product = ['id' => $item->id, 'item_name' => $item->item_name, 'cat_id' => $item->cat_id,
                'price' => $item->price, 'original_price' => $item->original_price, 'availability' => $state,
                'is_featured' => (int) $item->is_featured === 1, 'version' => $item->edit_token,
                'edit_url' => url('admin/item-'.$item->id)];
        @endphp
        <tr data-id="{{ $item->id }}" data-category="{{ $item->cat_id }}" data-state="{{ $state }}" data-orderable="{{ $item->is_orderable && (int) $category?->is_available === 1 ? '1' : '0' }}" data-featured="{{ (int) $item->is_featured === 1 ? '1' : '2' }}" data-product="{{ json_encode($product) }}">
            <td>{{ $loop->index }}</td>
            <td data-label="Termék"><div class="products-identity">
                <span class="products-drag" aria-hidden="true">⠿</span>
                <img class="products-photo" src="{{ $item->item_image?->image_url ?: helper::image_path('item-placeholder.png') }}" alt="" loading="lazy" width="48" height="48">
                <div><button class="products-name" type="button" data-quick-edit>{{ $item->item_name }}</button><small>#{{ $item->id }}</small></div>
            </div></td>
            <td data-label="Kategória"><span class="products-category">{{ $item->category_info?->category_name }}</span>@if ((int) $category?->is_available !== 1)<small class="products-category-off">Kategória inaktív</small>@endif</td>
            <td data-label="Ár" data-order="{{ $item->price }}"><strong class="products-price">{{ helper::currency_format($item->price) }}</strong>@if ($item->original_price > $item->price)<del>{{ helper::currency_format($item->original_price) }}</del>@endif</td>
            <td data-label="Elérhetőség"><button type="button" class="products-state products-state-{{ $state }}" data-edit-state title="Elérhetőség módosítása: {{ $item->item_name }}"><span aria-hidden="true"></span>{{ ['active' => 'Aktív', 'today' => 'Mára elfogyott', 'paused' => 'Szüneteltetve'][$state] }}</button></td>
            <td data-label="Ma nem elérhető"><button type="button" class="products-today-toggle {{ $item->today_unavailable ? 'is-unavailable' : '' }}"
                data-today-toggle data-today-url="{{ route('admin.item.today-unavailable') }}"
                aria-label="{{ $item->today_unavailable ? 'Újra elérhető' : 'Ma nem elérhető' }}: {{ $item->item_name }}">{{ $item->today_unavailable ? 'Újra elérhető' : 'Ma nem elérhető' }}</button></td>
            <td data-label="Mai ajánlat"><span class="products-featured {{ (int) $item->is_featured === 1 ? 'is-featured' : '' }}">{{ (int) $item->is_featured === 1 ? '★ Kiemelve' : 'Nincs kiemelve' }}</span></td>
            <td data-label="Műveletek"><div class="products-row-actions">
                <button type="button" class="btn products-quick-edit" data-quick-edit aria-label="Gyors szerkesztés: {{ $item->item_name }}"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Szerkesztés</button>
                <a href="{{ url('admin/item-'.$item->id) }}" class="products-detail-link" aria-label="Részletes szerkesztés: {{ $item->item_name }}">Részletek</a>
                <button type="button" class="products-delete" data-delete-url="{{ url('admin/item/delete') }}" aria-label="Termék törlése: {{ $item->item_name }}" title="Termék törlése"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
            </div><div class="products-move-buttons"><button type="button" data-move="-1" aria-label="{{ $item->item_name }} mozgatása felfelé">↑ Fel</button><button type="button" data-move="1" aria-label="{{ $item->item_name }} mozgatása lefelé">↓ Le</button></div></td>
            <td>{{ $item->updated_at?->format('Y-m-d H:i:s') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
