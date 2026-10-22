<?php

namespace App\Services;

use App\Helpers\helper;
use App\Models\Addons;
use App\Models\AddonsGroup;
use App\Models\Extra;
use App\Models\Item;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CatalogPricing
{
    private function ids(?string $value): array
    {
        if (trim((string) $value) === '') return [];
        $parts = preg_split('/\s*\|\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $id) {
            if (!ctype_digit($id) || (int) $id < 1) $this->invalid('Érvénytelen feltét vagy extra.');
        }
        return array_values(array_unique(array_map('intval', $parts)));
    }

    private function invalid(string $message): void
    {
        throw ValidationException::withMessages(['cart' => $message]);
    }

    public function selection(Item $item, ?string $addonIds, ?string $extraIds): array
    {
        $addonIds = $this->ids($addonIds);
        $extraIds = $this->ids($extraIds);
        $groupIds = array_filter(explode(',', (string) $item->addons_id));
        $groups = $groupIds ? AddonsGroup::whereIn('id', $groupIds)->where('is_deleted', 2)->where('is_available', 1)->get() : collect();
        $addons = $addonIds ? Addons::whereIn('id', $addonIds)->whereIn('addongroup_id', $groups->pluck('id'))
            ->where('is_deleted', 2)->where('is_available', 1)->get() : collect();
        $extras = $extraIds ? Extra::where('item_id', $item->id)->whereIn('id', $extraIds)->get() : collect();
        if ($addons->count() !== count($addonIds) || $extras->count() !== count($extraIds)) {
            $this->invalid('Érvénytelen feltét vagy extra.');
        }
        foreach ($groups as $group) {
            if (!Addons::where('addongroup_id', $group->id)->where('is_deleted', 2)->where('is_available', 1)->exists()) continue;
            $count = $addons->where('addongroup_id', $group->id)->count();
            $single = (int) $group->selection_count === 1;
            $minimum = (int) $group->selection_type === 1 ? ($single ? 1 : max(1, (int) $group->min_count)) : 0;
            $maximum = $single ? 1 : max(1, (int) $group->max_count);
            if ($count < $minimum || $count > $maximum) $this->invalid('A feltétek kiválasztása hiányos vagy túl sok.');
        }
        $price = (float) $item->price;
        if ((int) $item->is_top_deals === 1 && ($deal = helper::top_deals())) {
            $price = (int) $deal->offer_type === 1
                ? ($price > (float) $deal->offer_amount ? $price - (float) $deal->offer_amount : $price)
                : $price * (1 - (float) $deal->offer_amount / 100);
        }
        $values = ['item_price' => round(max(0, $price), 2), 'tax' => $item->tax ?? ''];
        foreach (['addons' => $addons, 'extras' => $extras] as $prefix => $choices) {
            if ($choices->contains(fn ($choice) => !is_numeric($choice->price) || (float) $choice->price < 0)) {
                $this->invalid('Hibás feltétár a katalógusban.');
            }
            $values[$prefix.'_id'] = $choices->pluck('id')->implode('| ');
            $values[$prefix.'_name'] = $choices->pluck('name')->implode('| ');
            $values[$prefix.'_price'] = $choices->pluck('price')->implode('| ');
            $values[$prefix.'_total_price'] = round($choices->sum('price'), 2);
        }
        return $values;
    }

    public function refresh(Collection $cart): float
    {
        $items = Item::visibleOnMenu()->whereIn('id', $cart->pluck('item_id'))->get()->keyBy('id');
        foreach ($cart as $line) {
            $item = $items->get($line->item_id);
            if (!$item || !$item->is_orderable) $this->invalid('A kosár egyik étele már nem elérhető.');
            if ((int) $line->qty < 1 || (int) $line->qty > 100) $this->invalid('Érvénytelen mennyiség.');
            $line->forceFill($this->selection($item, $line->addons_id, $line->extras_id));
            $line->item_name = $item->item_name;
        }
        return round($cart->sum(fn ($line) => ((float) $line->item_price + (float) $line->addons_total_price + (float) $line->extras_total_price) * (int) $line->qty), 2);
    }
}
