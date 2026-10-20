<?php

namespace App\Http\Controllers\front;

use App\Helpers\helper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);
        $user_id = @Auth::user()->id;
        $session_id = Session::getId();
        $topdeals = helper::top_deals();

        $categorydata = Category::where('slug', $request->category)->where('is_available', 1)->where('is_deleted', 2)->first();
        if (!$categorydata) {
            return redirect()->route('categories');
        }
        $subcategories = Subcategory::where('cat_id', @$categorydata->id)->where('is_available', 1)->where('is_deleted', 2)->get();


        if ($user_id != null) {

            $getitemlist = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('favorite', function ($query) use ($user_id) {
                    $query->on('favorite.item_id', '=', 'item.id')
                        ->where('favorite.user_id', '=', $user_id);
                })
                ->leftJoin('cart', function ($query) use ($user_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.user_id', '=', $user_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->visibleOnMenu()
                ->where('item.cat_id', @$categorydata->id)
                ->orderBy('item.reorder_id');
        } else {
            $getitemlist = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->visibleOnMenu()
                ->where('item.cat_id', @$categorydata->id)
                ->orderBy('item.reorder_id');
        }

        if ($request->has('subcategory') && $request->subcategory != "") {
            $subcatdata = Subcategory::where('slug', $request->subcategory)->where('cat_id', $categorydata->id)->where('is_available', 1)->where('is_deleted', 2)->first();
            if (empty($subcatdata)) {
                return redirect()->back();
            }
            $getitemlist = $getitemlist->where('item.subcat_id', @$subcatdata->id);
        }
        if ($request->filled('q')) {
            $getitemlist->where('item.item_name', 'like', '%'.trim($request->input('q')).'%');
        }
        $getitemlist = $getitemlist->orderByDesc('item.id')->paginate(16);
        return view('web.menu', compact('topdeals', 'categorydata', 'subcategories', 'getitemlist'));
    }
}
