<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GiftPromotion;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GiftPromotionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless((int) auth()->user()->type === 1, 403);
        $ready = Schema::hasTable('gift_promotions');
        $promotions = $ready ? GiftPromotion::orderByDesc('id')->get() : collect();
        $editing = $request->filled('edit') && $ready ? GiftPromotion::findOrFail($request->edit) : new GiftPromotion();
        $categories = Category::where('is_available', 1)->where('is_deleted', 2)->orderBy('category_name')->get();
        $items = Item::visibleOnMenu()->orderBy('item_name')->get();
        return view('admin.gift-promotions.index', compact('ready', 'promotions', 'editing', 'categories', 'items'));
    }

    public function save(Request $request)
    {
        abort_unless((int) auth()->user()->type === 1, 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'trigger_type' => ['required', Rule::in(['category', 'items'])],
            'category_id' => ['nullable', 'required_if:trigger_type,category', 'integer', 'exists:categories,id'],
            'trigger_item_ids' => ['nullable', 'required_if:trigger_type,items', 'array', 'min:1'],
            'trigger_item_ids.*' => ['integer', 'distinct', 'exists:item,id'],
            'required_qty' => ['required', 'integer', 'min:1', 'max:100'],
            'gift_item_ids' => ['required', 'array', 'min:1', 'max:100'],
            'gift_item_ids.*' => ['integer', 'distinct', 'exists:item,id'],
            'gift_qty' => ['required', 'integer', 'min:1', 'max:10'],
            'active' => ['nullable', 'boolean'], 'repeat' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('starts_at'), 'after_or_equal:starts_at')],
        ]);
        $promotion = $request->filled('id') ? GiftPromotion::findOrFail($request->integer('id')) : new GiftPromotion();
        $data['category_id'] = $data['trigger_type'] === 'category' ? $data['category_id'] : null;
        $data['trigger_item_ids'] = $data['trigger_type'] === 'items' ? array_map('intval', $data['trigger_item_ids']) : [];
        $data['gift_item_ids'] = array_map('intval', $data['gift_item_ids']);
        $data['active'] = $request->boolean('active');
        $data['repeat'] = $request->boolean('repeat');
        unset($data['trigger_type']);
        $promotion->fill($data)->save();
        return redirect()->route('admin.gift-promotions')->with('success', 'Az akciót elmentettük.');
    }
}
