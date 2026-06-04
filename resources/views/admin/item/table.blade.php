
<table id="itemsTable" class="table table-striped table-bordered zero-configuration">

    <thead>
        <tr>
            <th></th>
            <th>#</th>
            <th>{{ trans('labels.name') }}</th>
            <th>{{ trans('labels.category') }}</th>
            <th>{{ trans('labels.featured') }}</th>
            <th>{{ trans('labels.status') }}</th>
            <th>{{ __('Ma nem elérhető') }}</th>

            <th>{{ trans('labels.created_date') }}</th>
            <th>{{ trans('labels.updated_date') }}</th>
            <th>{{ trans('labels.action') }}</th>
        </tr>
    </thead>
    <tbody id="tabledetails" data-url="{{ url('admin/item/reorder_item') }}">
        @php $i = 1; @endphp
        @foreach ($getitem as $item)
            <tr class="row1" data-id="{{ $item->id }}">
                <td><a tooltip="{{ trans('labels.move') }}"><i class="fa-light fa-up-down-left-right mx-2"></i></a></td>
                <td>@php echo $i++; @endphp</td>
                <td><img @if ($item->item_type == 1) src="{{ helper::image_path('veg.svg') }}" @else src="{{ helper::image_path('nonveg.svg') }}" @endif
                        class="item-type-img" alt=""> {{ $item->item_name }}</td>
                <td>{{ @$item['category_info']->category_name }}</td>
                <td>
                    @if ($item->is_featured == 1)
                        <a class="btn btn-sm btn-success square" tooltip="{{ trans('labels.active') }}"
                            @if (env('Environment') == 'sendbox') onclick="myFunction()"
                    @else onclick="StatusFeatured('{{ $item->id }}','2','{{ URL::to('admin/item/featured') }}')" @endif><i
                                class="fa-sharp fa-solid fa-check"></i></a>
                    @else
                        <a class="btn btn-sm btn-danger square" tooltip="{{ trans('labels.deactive') }}"
                            @if (env('Environment') == 'sendbox') onclick="myFunction()"
                    @else onclick="StatusFeatured('{{ $item->id }}','1','{{ URL::to('admin/item/featured') }}')" @endif><i
                                class="fa-sharp fa-solid fa-xmark"></i></a>
                    @endif
                </td>
                <td>
                    @if ($item->item_status == 1)
                        <a class="btn btn-sm btn-success square" tooltip="{{ trans('labels.active') }}"
                            @if (env('Environment') == 'sendbox') onclick="myFunction()"
                    @else onclick="StatusUpdate('{{ $item->id }}','2','{{ URL::to('admin/item/status') }}')" @endif>
                            <i class="fa-sharp fa-solid fa-check"></i></a>
                    @else
                        <a class="btn btn-sm btn-danger square" tooltip="{{ trans('labels.deactive') }}"
                            @if (env('Environment') == 'sendbox') onclick="myFunction()"
                    @else onclick="StatusUpdate('{{ $item->id }}','1','{{ URL::to('admin/item/status') }}')" @endif>
                            <i class="fa-sharp fa-solid fa-xmark"></i></a>
                    @endif
                </td>


                <td>
                    @if ($item->today_unavailable == 0)
                        {{-- Default: nem elérhető (piros X) --}}
                        <a class="btn btn-sm btn-danger square"
                           tooltip="Ma nem elérhető"
                           onclick="ToggleTodayUnavailable('{{ $item->id }}','{{ route('admin.item.today-unavailable') }}')">
                            <i class="fa-sharp fa-solid fa-xmark"></i>
                        </a>
                    @else
                        {{-- Ha be van kapcsolva: elérhető (zöld pipa) --}}
                        <a class="btn btn-sm btn-success square"
                           tooltip="Ma elérhető (kattintva letiltod mára)"
                           onclick="ToggleTodayUnavailable('{{ $item->id }}','{{ route('admin.item.today-unavailable') }}')">
                            <i class="fa-sharp fa-solid fa-check"></i>
                        </a>
                    @endif
                </td>





                <td>
                    {{ helper::date_format($item->created_at) }} <br>
                    {{ helper::time_format($item->created_at) }}
                </td>
                <td>
                    {{ helper::date_format($item->updated_at) }} <br>
                    {{ helper::time_format($item->updated_at) }}
                </td>
                <td>
                    <div class="d-flex flex-wrap gap-1">
                        <a class="btn btn-sm btn-info square" tooltip="{{ trans('labels.edit') }}"
                            href="{{ URL::to('admin/item-' . $item->id) }}"> <i class="fa-solid fa-pen-to-square"></i></a>
                        <a class="btn btn-sm btn-danger square" tooltip="{{ trans('labels.delete') }}"
                            @if (env('Environment') == 'sendbox') onclick="myFunction()"
                        @else onclick="Delete('{{ $item->id }}','{{ URL::to('admin/item/delete') }}')" @endif>
                            <i class="fa fa-trash"></i></a>
                    </div>
                </td>


            </tr>
        @endforeach
    </tbody>
</table>
<script>
    function ToggleTodayUnavailable(id, url) {
        // ha van, vedd a globális tokenből; ha nincs, így is jó:
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        $.ajax({
            url: url,
            method: 'POST',
            data: { id: id, _token: token },
            beforeSend: function(){
                // opcionális: loading állapot
            },
            success: function(res){
                if (res && res.success) {
                    // 1) Minimál: frissítsük az oldalt, biztos vizuális állapotváltás
                    location.reload();

                    // 2) Ha nem akarsz reloadot, akkor itt cseréld a gomb színét/ikonját
                    // és tooltipjét (de a reload a legegyszerűbb és bombabiztos).
                } else {
                    alert('Nem sikerült frissíteni.');
                }
            },
            error: function(xhr){
                console.error(xhr.responseText || xhr.statusText);
                alert('Hiba történt a frissítés közben.');
            }
        });
    }
</script>
