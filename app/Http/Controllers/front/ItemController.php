<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Addons;
use App\Helpers\helper;
use App\Models\AddonsGroup;
use App\Models\Extra;
use App\Models\Ratting;
use App\Models\SystemAddons;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;

class ItemController extends Controller
{
    public function showitem(Request $request)
    {
        $topdeals = helper::top_deals();
        $user_id = @Auth::user()->id;
        $iteminfo = Item::with(['subcategory_info', 'category_info', 'item_image'])
            ->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'))
            ->leftJoin('favorite', function ($query) use ($user_id) {
                $query->on('favorite.item_id', '=', 'item.id')
                    ->where('favorite.user_id', '=', $user_id);
            })
            ->where('item.slug', '=', $request->slug)
            ->visibleOnMenu()
            ->first();
        if (!$iteminfo || !$iteminfo->is_orderable) {
            return response()->json(['status' => 0, 'message' => 'Ez az étel jelenleg nem elérhető.']);
        }
        $itemdata = array(
            "id" => $iteminfo->id,
            "slug" => $iteminfo->slug,
            "item_name" => $iteminfo->item_name,
            "item_type" => $iteminfo->item_type,
            "item_type_image" => $iteminfo->item_type == 1 ? helper::image_path("veg.svg") : helper::image_path("nonveg.svg"),
            "price" => $iteminfo->price,
            "video_url" => $iteminfo->video_url,
            "is_top_deals" => $iteminfo->is_top_deals,
            "tax" => $iteminfo->tax,
            "image_name" => @$iteminfo['item_image']->image_name,
            "is_favorite" => $iteminfo->is_favorite,
            "addons_group" => AddonsGroup::select('id', 'name', 'selection_type', 'selection_count', 'min_count', 'max_count')->whereIn('id', explode(',', $iteminfo->addons_id))->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get(),
            "addons" => Addons::select('id', 'addongroup_id', 'name', 'price')->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get(),
            "extras" => Extra::where('item_id', $iteminfo->id)->get(),
        );
        foreach ($itemdata['addons_group'] as $addons_group) {
            $addons_group->availableAddons = $itemdata['addons']->where('addongroup_id', $addons_group->id);
        }
        if ($request->ajax()) {
            $html = view('web.addonsmodal', compact('topdeals', 'itemdata'))->render();
            return response()->json(['status' => 1, 'output' => $html, 'id' => $iteminfo->id], 200);
        }
    }
    public function itemdetails(Request $request)
    {
        $user_id = @Auth::user()->id;
        $session_id = Session::getId();
        $topdeals = helper::top_deals();

        if ($user_id != null) {
            $getitemdata = Item::with('category_info', 'subcategory_info', 'item_images', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->where('item.slug', '=', $request->slug)
                ->visibleOnMenu()
                ->firstOrFail();
            $getitemdata['addons_group'] = AddonsGroup::select('id', 'name', 'selection_type', 'selection_count', 'min_count', 'max_count')->whereIn('id', explode(',', $getitemdata->addons_id))->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
            $getitemdata['addons'] = Addons::select('id', 'addongroup_id', 'name', 'price')->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
            foreach ($getitemdata['addons_group'] as $addons_group) {
                $addons_group->availableAddons = $getitemdata['addons']->where('addongroup_id', $addons_group->id);
            }
            $getitemdata['extras'] = Extra::where('item_id', $getitemdata->id)->get();
            $getrelateditems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->orderByDesc('item.id')
                ->where('item.id', '!=', @$getitemdata->id)
                ->where('item.cat_id', '=', @$getitemdata->cat_id)
                ->visibleOnMenu()
                ->take(3)->get();
        } else {
            $getitemdata = Item::with('category_info', 'subcategory_info', 'item_images')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->where('item.slug', '=', $request->slug)
                ->visibleOnMenu()
                ->firstOrFail();
            $getitemdata['addons_group'] = AddonsGroup::select('id', 'name', 'selection_type', 'selection_count', 'min_count', 'max_count')->whereIn('id', explode(',', $getitemdata->addons_id))->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
            $getitemdata['addons'] = Addons::select('id', 'addongroup_id', 'name', 'price')->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
            foreach ($getitemdata['addons_group'] as $addons_group) {
                $addons_group->availableAddons = $getitemdata['addons']->where('addongroup_id', $addons_group->id);
            }
            $getrelateditems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->orderByDesc('item.id')
                ->where('item.id', '!=', @$getitemdata->id)
                ->where('item.cat_id', '=', @$getitemdata->cat_id)
                ->visibleOnMenu()
                ->take(3)->get();
        }
        $itemreviewdata = Ratting::publicReviews('product_review')->with('user_info')->select('id', 'ratting', 'comment', 'item_id', 'user_id', 'created_at')->where('item_id', $getitemdata->id)->where('status', 1)->get();
        // These approved reviews are already loaded for the page. Reuse them
        // instead of issuing five more count queries (and settings lookups).
        $counts = $itemreviewdata->countBy('ratting');
        $data = [];
        foreach ([1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five'] as $stars => $label) {
            $data[$label.'staraverage'] = $counts->get($stars, 0);
        }

        return view('web.productdetails', $data, compact('topdeals', 'getitemdata', 'getrelateditems', 'itemreviewdata'));
    }
    public function search(Request $request)
    {
        $user_id = @Auth::user()->id;
        $session_id = Session::getId();
        $topdeals = helper::top_deals();

        if ($user_id != null) {
            $getsearchitems = array();
            if ($request->has('itemname') && $request->itemname != "") {
                $getsearchitems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                    ->leftJoin('order_details', 'order_details.item_id', 'item.id')
                    ->leftJoin('favorite', function ($query) use ($user_id) {
                        $query->on('favorite.item_id', '=', 'item.id')
                            ->where('favorite.user_id', '=', $user_id);
                    })
                    ->leftJoin('cart', function ($query) use ($user_id) {
                        $query->on('cart.item_id', '=', 'item.id')
                            ->where('cart.user_id', '=', $user_id)
                            ->where('cart.buynow', '=', '0');
                    })
                    ->groupBy('order_details.item_id', 'item.id', 'cart.item_id')
                    ->where('item.item_name', 'like', '%' . $request->itemname . '%')
                    ->visibleOnMenu()
                    ->orderByDesc('item.id')->paginate(16);
            }
        } else {
            $getsearchitems = array();
            if ($request->has('itemname') && $request->itemname != "") {
                $getsearchitems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                    ->leftJoin('order_details', 'order_details.item_id', 'item.id')
                    ->leftJoin('cart', function ($query) use ($session_id) {
                        $query->on('cart.item_id', '=', 'item.id')
                            ->where('cart.session_id', '=', $session_id)
                            ->where('cart.buynow', '=', '0');
                    })
                    ->groupBy('order_details.item_id', 'item.id', 'cart.item_id')
                    ->where('item.item_name', 'like', '%' . $request->itemname . '%')
                    ->visibleOnMenu()
                    ->orderByDesc('item.id')->paginate(16);
            }
        }

        return view('web.search', compact('topdeals', 'getsearchitems'));
    }
    public function viewall(Request $request)
    {
        $user_id = @Auth::user()->id;
        $session_id = Session::getId();
        $getsearchitems = array();
        $topdeals = helper::top_deals();
        $offer_price = 0;
        if ($topdeals != null && $topdeals->offer_type == 1) {
            $offer_price = (int)$topdeals->offer_amount;
        }

        if ($user_id != null) {
            if ($request->has('type') && $request->type != "" && in_array($request->type, array('todayspecial', 'topitems', 'recommended', 'topdeals'))) {
                $getsearchitems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'), DB::raw('count(order_details.item_id) as item_order_counter'))
                    ->leftJoin('order_details', 'order_details.item_id', 'item.id')
                    ->leftJoin('favorite', function ($query) use ($user_id) {
                        $query->on('favorite.item_id', '=', 'item.id')
                            ->where('favorite.user_id', '=', $user_id);
                    })
                    ->leftJoin('cart', function ($query) use ($user_id) {
                        $query->on('cart.item_id', '=', 'item.id')
                            ->where('cart.user_id', '=', $user_id)
                            ->where('cart.buynow', '=', '0');
                    })
                    ->groupBy('item.id', 'cart.item_id')->visibleOnMenu();
                if ($request->has('type') && $request->type != "") {
                    if ($request->type == "todayspecial") {
                        $getsearchitems = $getsearchitems->where('item.is_featured', '1')->orderBy('item.reorder_id');
                    }
                    if ($request->type == "topitems") {
                        $getsearchitems = $getsearchitems->orderByDesc('item_order_counter');
                    }
                    if ($request->type == "recommended") {
                        $getsearchitems = $getsearchitems->inRandomOrder();
                    }
                    if ($request->type == "topdeals") {
                        if (@helper::checkaddons('top_deals')) {
                            if ($topdeals != null && $topdeals->top_deals_switch == 1) {
                                $getsearchitems = $getsearchitems->where('item.is_top_deals', '1')->where('item.price', '>', $offer_price)->orderBy('item.reorder_id');
                            } else {
                                abort(404);
                            }
                        } else {
                            abort(404);
                        }
                    }
                }
                if ($request->has('filter') && $request->filter != "") {
                    if ($request->filter == "veg") {
                        $getsearchitems = $getsearchitems->where('item.item_type', 1);
                    }
                    if ($request->filter == "nonveg") {
                        $getsearchitems = $getsearchitems->where('item.item_type', 2);
                    }
                }
                $getsearchitems = $getsearchitems->paginate(15);
            }
        } else {
            if ($request->has('type') && $request->type != "" && in_array($request->type, array('todayspecial', 'topitems', 'recommended', 'topdeals'))) {
                $getsearchitems = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'), DB::raw('count(order_details.item_id) as item_order_counter'))
                    ->leftJoin('order_details', 'order_details.item_id', 'item.id')
                    ->leftJoin('cart', function ($query) use ($session_id) {
                        $query->on('cart.item_id', '=', 'item.id')
                            ->where('cart.session_id', '=', $session_id)
                            ->where('cart.buynow', '=', '0');
                    })
                    ->groupBy('item.id', 'cart.item_id')->visibleOnMenu();
                if ($request->has('type') && $request->type != "") {
                    if ($request->type == "todayspecial") {
                        $getsearchitems = $getsearchitems->where('item.is_featured', '1')->orderBy('item.reorder_id');
                    }
                    if ($request->type == "topitems") {
                        $getsearchitems = $getsearchitems->orderByDesc('item_order_counter');
                    }
                    if ($request->type == "recommended") {
                        $getsearchitems = $getsearchitems->inRandomOrder();
                    }
                    if ($request->type == "topdeals") {
                        if (@helper::checkaddons('top_deals')) {
                            if ($topdeals != null && $topdeals->top_deals_switch == 1) {
                                $getsearchitems = $getsearchitems->where('item.is_top_deals', '1')->where('item.price', '>', $offer_price)->orderBy('item.reorder_id');
                            } else {
                                abort(404);
                            }
                        } else {
                            abort(404);
                        }
                    }
                }
                if ($request->has('filter') && $request->filter != "") {
                    if ($request->filter == "veg") {
                        $getsearchitems = $getsearchitems->where('item.item_type', 1);
                    }
                    if ($request->filter == "nonveg") {
                        $getsearchitems = $getsearchitems->where('item.item_type', 2);
                    }
                }
                $getsearchitems = $getsearchitems->paginate(15);
            }
        }
        return view('web.viewall', compact('getsearchitems', 'topdeals'));
    }

    public function getitemallergens(Request $request)
    {
        $itemAllergens = Item::where('id', $request->item_id)->first()->item_allergens;
        return response()->json(['item_allergens' => $itemAllergens]);
    }
}
