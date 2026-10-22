@php $selectedType = (int) old('item_type', $getitem->item_type ?? 1); @endphp
<fieldset class="item-type-fieldset">
    <legend>{{ trans('labels.item_type') }} <span class="text-danger">*</span></legend>
    <div class="item-type-options">
        <label class="item-type-option" for="veg">
            <input type="radio" name="item_type" id="veg" value="1" required @checked($selectedType === 1)>
            <span>Vegetáriánus</span>
        </label>
        <label class="item-type-option" for="nonveg">
            <input type="radio" name="item_type" id="nonveg" value="2" required @checked($selectedType === 2)>
            <span>Húsos</span>
        </label>
    </div>
</fieldset>
