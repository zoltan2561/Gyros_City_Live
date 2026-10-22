<?php

namespace App\Http\Controllers\addons;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ratting;
use Illuminate\Support\Facades\Auth;

class StoreReviewController extends Controller
{
    public function index()
    {
        $getstorereviewlist = Ratting::featuredTestimonials()->orderBy('reorder_id')->paginate(12);
        return view('admin.store_review.store_review', compact('getstorereviewlist'));
    }
    public function add()
    {
        return view('admin.store_review.add');
    }
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'ratting' => 'required|integer|between:1,5', 'comment' => 'required|string|max:255', 'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120']);
        $image = 'store_review-' . uniqid() . '.' . $request->image->extension();
        $request->image->move(storage_path('app/public/admin-assets/images/reviews'), $image);
        $store_review = new Ratting();
        $store_review->user_id = Auth::user()->id;
        $store_review->reorder_id = 0;
        $store_review->status = 1;
        $store_review->name = $request->name;
        $store_review->ratting = $request->ratting;
        $store_review->comment = $request->comment;
        $store_review->image = $image;
        $store_review->save();
        return redirect('admin/store-review')->with('success', trans('messages.success'));
    }
    public function show(Request $request)
    {
        $getstorereviewdata = Ratting::featuredTestimonials()->findOrFail($request->id);
        return view('admin.store_review.edit', compact('getstorereviewdata'));
    }
    public function update(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255', 'ratting' => 'required|integer|between:1,5', 'comment' => 'required|string|max:255', 'image' => 'nullable|image|mimes:jpeg,png,gif,webp|max:5120']);
        $store_review = Ratting::featuredTestimonials()->findOrFail($request->id);
        if ($request->hasFile('image')) {
            if ($store_review->image && file_exists(storage_path('app/public/admin-assets/images/reviews/' . $store_review->image))) {
                unlink(storage_path('app/public/admin-assets/images/reviews/' . $store_review->image));
            }
            $image = 'store_review-' . uniqid() . '.' . $request->image->extension();
            $request->image->move(storage_path('app/public/admin-assets/images/reviews'), $image);
            $store_review->image = $image;
            $store_review->save();
        }
        $store_review->name = $request->name;
        $store_review->ratting = $request->ratting;
        $store_review->comment = $request->comment;
        $store_review->save();
        return redirect('admin/store-review')->with('success', trans('messages.success'));
    }
    public function destroy(Request $request)
    {
        $store_review = Ratting::featuredTestimonials()->findOrFail($request->id);
        if ($store_review->image && file_exists(storage_path('app/public/admin-assets/images/reviews/' . $store_review->image))) {
            unlink(storage_path('app/public/admin-assets/images/reviews/' . $store_review->image));
        }
        $store_review->delete();
        if ($store_review) {
            return 1;
        } else {
            return 0;
        }
    }
    public function reorder_ratting(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*.id' => 'required|integer', 'order.*.position' => 'required|integer|min:0']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            foreach ($request->order as $order) {
                $ratting = Ratting::featuredTestimonials()->findOrFail($order['id']);
                $ratting->reorder_id = $order['position'];
                $ratting->save();
            }
        });
        return response()->json(['status' => 1, 'msg' => 'Update Successfully!!'], 200);
    }
}
