<?php

namespace App\Http\Controllers\addons;

use App\Helpers\helper;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Ratting;
use App\Models\Settings;
use App\Models\SystemAddons;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RattingController extends Controller
{
    public function index(Request $request)
    {
        $sorter = $request->input('item_id', $request->input('item_name'));
        $getproduct = Item::select('id', 'item_name')->orderBy('item_name')->get();
        $reviews = Ratting::customerReviews()->with('item_info', 'user_info')->when($sorter, function ($query, $itemId) {
            if ($itemId === 'restaurant') return $query->whereNull('item_id');
            $query->where('item_id', $itemId);
        })->when($request->input('kind') === 'product', fn ($query) => $query->whereNotNull('item_id'))
            ->when($request->input('kind') === 'restaurant', fn ($query) => $query->whereNull('item_id'))
            ->when(in_array((string) $request->input('status'), ['1', '2'], true), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('admin.reviews.table', ['getreview' => $reviews]);
        }

        return view('admin.reviews.reviews', [
            'getproduct' => $getproduct,
            'sorter' => $sorter,
            'getreview' => $reviews,
            'getsettings' => Settings::first(),
        ]);
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'reviews_enabled' => ['nullable', Rule::in([0, 1, 2])],
            'review_approved_status' => ['nullable', Rule::in([0, 1, 2])],
            'review_auto_approved' => ['nullable', 'array'],
            'review_auto_approved.*' => ['integer', 'between:1,5'],
        ]);
        $settings = Settings::firstOrFail();
        $settings->reviews_enabled = (int) $request->input('reviews_enabled') === 1 ? 1 : 2;
        $enabled = (int) $request->input('review_approved_status') === 1;
        $ratings = $request->input('review_auto_approved', []);
        $settings->review_approved_status = $enabled ? 1 : 2;
        $settings->review_auto_approved = $enabled
            ? implode(',', array_values(array_intersect(['1', '2', '3', '4', '5'], array_map('strval', (array) $ratings))))
            : $settings->review_auto_approved;
        DB::transaction(function () use ($settings) {
            $settings->save();
            if ((int) $settings->reviews_enabled === 1) {
                SystemAddons::whereIn('unique_identifier', ['product_review', 'store_review'])->update(['activated' => 1]);
            }
        });

        return back()->with('success', trans('messages.success'));
    }

    public function destroy(Request $request)
    {
        $request->validate(['id' => ['required', 'integer']]);
        DB::transaction(function () use ($request) {
            Ratting::customerReviews()->findOrFail($request->input('id'))->delete();
        });

        return $request->expectsJson() || $request->ajax()
            ? response('1') : back()->with('success', trans('messages.success'));
    }

    public function status(Request $request)
    {
        $request->validate(['id' => ['required', 'integer'], 'status' => ['required', Rule::in([1, 2])]]);
        DB::transaction(function () use ($request) {
            $review = Ratting::customerReviews()->findOrFail($request->input('id'));
            $review->status = (int) $request->input('status');
            $review->save();
        });

        return $request->expectsJson() || $request->ajax()
            ? response('1') : back()->with('success', trans('messages.success'));
    }

    public function addreview(Request $request)
    {
        $kind = $request->filled('item_id') ? 'product_review' : 'store_review';
        abort_unless(helper::reviews_enabled($kind), 403);

        if (!Auth::check() || (int) Auth::user()->type !== 2
            || (int) Auth::user()->is_available !== 1 || (int) Auth::user()->is_deleted !== 2) {
            return redirect('/login')->with('error', trans('messages.user_required'));
        }

        $validated = $request->validate([
            'item_id' => ['nullable', 'integer', Rule::exists('item', 'id')->where('item_status', 1)],
            'ratting' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = Settings::first();
        $approvedRatings = array_map('intval', explode(',', (string) ($settings->review_auto_approved ?? '')));
        $autoApprove = (int) ($settings->review_approved_status ?? 2) === 1
            && in_array((int) $validated['ratting'], $approvedRatings, true);

        DB::transaction(function () use ($validated, $autoApprove) {
            // Serialize each customer's submissions, including null restaurant targets.
            User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
            $itemId = $validated['item_id'] ?? null;
            if (Ratting::customerReviews()->where('user_id', Auth::id())->where('item_id', $itemId)->exists()) {
                throw ValidationException::withMessages(['ratting' => trans('reviews.duplicate')]);
            }
            $review = new Ratting([
                'user_id' => Auth::id(), 'item_id' => $itemId, 'ratting' => $validated['ratting'],
                'comment' => $validated['comment'] ?? '', 'status' => $autoApprove ? 1 : 2,
            ]);
            $review->reorder_id = 0;
            $review->save();
        });

        return back()->with('success', trans('reviews.submitted'));
    }
}
