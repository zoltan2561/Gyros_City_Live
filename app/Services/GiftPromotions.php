<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\GiftPromotion;
use App\Models\Item;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class GiftPromotions
{
    public function transferGuestChoices(int $userId, array $buckets, bool $previousQuickMoved): void
    {
        $append = function (string $from, string $to) {
            $choices = session()->pull($from, []);
            $saved = session($to, []);
            foreach ($choices as $promotion => $slots) {
                $saved[$promotion] = array_merge($saved[$promotion] ?? [], $slots);
            }
            if ($saved !== []) session()->put($to, $saved);
        };
        if ($previousQuickMoved) $append('gift_choices.'.$userId.'.1', 'gift_choices.'.$userId.'.0');
        foreach ($buckets as $bucket) $append('gift_choices.guest.'.$bucket, 'gift_choices.'.$userId.'.'.$bucket);
        // offers() rechecks entitlement and availability against the combined cart on the next read.
    }

    public function cart(int $buynow = 0): Collection
    {
        return Cart::where('buynow', $buynow)
            ->when(Auth::check(), fn ($q) => $q->where('user_id', Auth::id()),
                fn ($q) => $q->whereNull('user_id')->where('session_id', session()->getId()))
            ->get();
    }

    public function active(): Collection
    {
        // Rolling deployment: the storefront also works before the new migration is applied.
        if (!Schema::hasTable('gift_promotions')) {
            return collect();
        }
        $today = now('Europe/Budapest')->toDateString();
        return GiftPromotion::where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->orderBy('id')->get();
    }

    public function offers(Collection $cart, int $buynow = 0): Collection
    {
        $promotions = $this->active();
        if ($promotions->isEmpty()) {
            session()->forget($this->sessionKey($buynow));
            return collect();
        }
        $ids = $cart->pluck('item_id')->merge($promotions->pluck('gift_item_ids')->flatten())->unique();
        $items = Item::visibleOnMenu()->with(['item_image', 'extras'])->whereIn('id', $ids)->get()->keyBy('id');
        $saved = session($this->sessionKey($buynow), []);

        $offers = $promotions->map(function ($promotion) use ($cart, $items, $saved) {
            $qty = (int) $cart->sum(function ($line) use ($promotion, $items) {
                $item = $items->get($line->item_id);
                if (!$item || !$item->is_orderable) {
                    return 0;
                }
                $matches = $promotion->category_id
                    ? (int) $item->cat_id === (int) $promotion->category_id
                    : in_array((int) $item->id, $promotion->trigger_item_ids ?? [], true);
                return $matches ? max(0, (int) $line->qty) : 0;
            });
            $batches = intdiv($qty, max(1, $promotion->required_qty));
            $earned = ($promotion->repeat ? $batches : min(1, $batches)) * $promotion->gift_qty;
            $gifts = collect($promotion->gift_item_ids)->map(fn ($id) => $items->get($id))
                ->filter(fn ($item) => $item && $item->is_orderable)->values();
            $choices = []; $extraChoices = []; $savedChoices = []; $pending = false;
            for ($slot = 0; $slot < $earned; $slot++) {
                $savedChoice = $saved[$promotion->id][$slot] ?? 0;
                $id = (int) (is_array($savedChoice) ? ($savedChoice['item_id'] ?? 0) : $savedChoice);
                $item = $gifts->firstWhere('id', $id);
                $extraId = (int) (is_array($savedChoice) ? ($savedChoice['extra_id'] ?? 0) : 0);
                $extra = $item?->extras->firstWhere('id', $extraId);
                $choices[] = $item ? $id : null;
                $extraChoices[] = $extra ? (int) $extra->id : null;
                $complete = $item && ($item->extras->isEmpty() || $extra);
                $pending = $pending || !$complete;
                $savedChoices[] = $item ? ['item_id' => $id, 'extra_id' => $extra?->id] : null;
            }
            return compact('promotion', 'qty', 'earned', 'gifts', 'choices', 'extraChoices', 'savedChoices', 'pending');
        });
        // Drop choices for lost entitlement so a later qualifying order asks again.
        session()->put($this->sessionKey($buynow), $offers->mapWithKeys(
            fn ($offer) => [$offer['promotion']->id => $offer['savedChoices']]
        )->all());
        return $offers;
    }

    public function choose(int $promotionId, array $choices, int $buynow = 0, array $extraChoices = []): bool
    {
        $offer = $this->offers($this->cart($buynow), $buynow)->first(fn ($offer) => $offer['promotion']->id === $promotionId);
        if (!$offer || $offer['earned'] < 1 || count($choices) !== $offer['earned']) return false;
        $saved = [];
        foreach (array_values($choices) as $slot => $choice) {
            $item = $offer['gifts']->firstWhere('id', (int) $choice);
            if (!$item) return false;
            $extraId = (int) ($extraChoices[$slot] ?? 0);
            $extra = $item->extras->firstWhere('id', $extraId);
            if (($item->extras->isNotEmpty() && !$extra) || ($extraId && !$extra)) return false;
            $saved[] = ['item_id' => (int) $item->id, 'extra_id' => $extra?->id];
        }
        session()->put($this->sessionKey($buynow).'.'.$promotionId, $saved);
        return true;
    }

    public function error(Collection $cart, int $buynow = 0): ?string
    {
        $items = $cart->isEmpty() ? collect() : Item::visibleOnMenu()->whereIn('id', $cart->pluck('item_id'))->get()->keyBy('id');
        foreach ($cart as $line) {
            if (!$items->get($line->item_id)?->is_orderable) {
                return $line->item_name.' jelenleg nem elérhető. Kérjük, vedd ki a kosárból.';
            }
        }
        foreach ($this->offers($cart, $buynow) as $offer) {
            if ($offer['gifts']->isNotEmpty() && $offer['pending']) {
                return 'Válaszd ki az ajándékot a kosárban: '.$offer['promotion']->name;
            }
        }
        return null;
    }

    public function giftLines(Collection $cart, int $buynow = 0): Collection
    {
        $lines = collect();
        foreach ($this->offers($cart, $buynow) as $offer) {
            foreach ($offer['choices'] as $slot => $id) {
                $item = $offer['gifts']->firstWhere('id', $id);
                if (!$item) {
                    continue;
                }
                $extra = $item->extras->firstWhere('id', $offer['extraChoices'][$slot]);
                if ($item->extras->isNotEmpty() && !$extra) continue;
                $lines->push(new Cart([
                    'item_id' => $item->id, 'item_name' => 'Ajándék: '.$item->item_name,
                    'item_type' => $item->item_type, 'item_image' => $item->item_image?->image_name ?? '',
                    'qty' => 1, 'item_price' => 0, 'tax' => '',
                    'addons_total_price' => 0, 'extras_total_price' => 0,
                    'extras_id' => $extra?->id ? (string) $extra->id : null,
                    'extras_name' => $extra?->name, 'extras_price' => $extra ? '0' : null,
                ]));
            }
        }
        return $lines;
    }

    private function sessionKey(int $buynow): string
    {
        return 'gift_choices.'.(Auth::id() ?? 'guest').'.'.$buynow;
    }
}
