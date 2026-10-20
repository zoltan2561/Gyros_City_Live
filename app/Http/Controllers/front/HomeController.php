<?php

namespace App\Http\Controllers\front;

use App\helpers\helper;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Models\Item;
use App\Models\Banner;
use App\Models\Blogs;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Ratting;
use App\Models\Languages;
use App\Models\Settings;
use App\Models\Team;
use App\Models\WhyChooseUs;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $topdeals = helper::top_deals();
        $offer_price = 0;
        if ($topdeals != null && $topdeals->offer_type == 1) {
            $offer_price = (int)$topdeals->offer_amount;
        }
        $getgalleries = Gallery::select('image', DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/about') . "/', image) AS image_url"))->orderByDesc('id')->get();
        $user_id = @Auth::user()->id;
        $session_id = Session::getId();
        $sliders = Slider::with('item_info', 'category_info')->where('is_available', 1)->orderByDesc('id')->get();
        $bannerlist = Banner::with('item_info', 'category_info')->where('is_available', 1)->orderBy('reorder_id')->get();
        $banners = array();
        $banners['topbanners'] = array();
        $banners['bannersection1'] = array();
        $banners['bannersection2'] = array();
        $banners['bannersection3'] = array();
        foreach ($bannerlist as $bannerdata) {
            if ($bannerdata->section == 1) {
                $banners['topbanners'][] = array(
                    "id" => $bannerdata->id,
                    "item_id" => $bannerdata->item_id,
                    "cat_id" => $bannerdata->cat_id,
                    "image" => helper::image_path($bannerdata->image),
                    "item_info" => $bannerdata->item_info,
                    "category_info" => $bannerdata->category_info,
                );
            }
            if ($bannerdata->section == 2) {
                $banners['bannersection1'][] = array(
                    "id" => $bannerdata->id,
                    "item_id" => $bannerdata->item_id,
                    "cat_id" => $bannerdata->cat_id,
                    "image" => helper::image_path($bannerdata->image),
                    "item_info" => $bannerdata->item_info,
                    "category_info" => $bannerdata->category_info,
                );
            }
            if ($bannerdata->section == 3) {
                $banners['bannersection2'][] = array(
                    "id" => $bannerdata->id,
                    "item_id" => $bannerdata->item_id,
                    "cat_id" => $bannerdata->cat_id,
                    "image" => helper::image_path($bannerdata->image),
                    "item_info" => $bannerdata->item_info,
                    "category_info" => $bannerdata->category_info,
                );
            }
            if ($bannerdata->section == 4) {
                $banners['bannersection3'][] = array(
                    "id" => $bannerdata->id,
                    "item_id" => $bannerdata->item_id,
                    "cat_id" => $bannerdata->cat_id,
                    "image" => helper::image_path($bannerdata->image),
                    "item_info" => $bannerdata->item_info,
                    "category_info" => $bannerdata->category_info,
                );
            }
        }
        $storereviews = Ratting::where('user_id', '1')->orderBy('reorder_id')->get();
        $getteams = Team::select("id", "name", "designation", "fb", "youtube", "insta", "twitter", "description", "image")->orderBy('reorder_id')->take('6')->get();
        $getfaqs = Faq::select("id", "title", "description")->orderBy('reorder_id')->get();
        $getblogs = Blogs::orderBy('reorder_id')->take('3')->get();
        $getwhychooseus = WhyChooseUs::orderBy('reorder_id')->get();

        if ($user_id != null) {
            $topitemlist = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', 'order_details.qty as order_details_qty', DB::raw('count(order_details.item_id) as item_order_counter'), DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->orderByDesc('item_order_counter')
                ->visibleOnMenu()
                ->take(9)->get();

            $todayspecial = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->where('item.is_featured', '1')
                ->visibleOnMenu()
                ->orderBy('item.reorder_id')->take(8)->get();

            $topdealsproduct = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->where('item.is_top_deals', '1')
                ->visibleOnMenu()
                ->where('item.price', '>', $offer_price)
                ->orderBy('item.reorder_id')->take(8)->get();

            $recommended = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when favorite.item_id is null then 0 else 1 end) as is_favorite'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
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
                ->inRandomOrder()
                ->visibleOnMenu()
                ->take(9)->get();
        } else {
            $topitemlist = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', 'order_details.qty as order_details_qty', DB::raw('count(order_details.item_id) as item_order_counter'), DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('order_details', 'order_details.item_id', 'item.id')
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('order_details.item_id', 'item.id', 'cart.item_id')
                ->orderByDesc('item_order_counter')
                ->visibleOnMenu()
                ->take(8)->get();

            $todayspecial = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->where('item.is_featured', '1')
                ->visibleOnMenu()
                ->orderBy('item.reorder_id')
                ->take(8)->get();

            $topdealsproduct = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->where('item.is_top_deals', '1')
                ->visibleOnMenu()
                ->where('item.price', '>', $offer_price)
                ->orderBy('item.reorder_id')->take(10)->get();

            $recommended = Item::with('category_info', 'subcategory_info', 'item_image')->select('item.*', DB::raw('(case when item.price is null then 0 else item.price end) as item_price'), DB::raw('(case when cart.item_id is null then 0 else 1 end) as is_cart'))
                ->leftJoin('cart', function ($query) use ($session_id) {
                    $query->on('cart.item_id', '=', 'item.id')
                        ->where('cart.session_id', '=', $session_id)
                        ->where('cart.buynow', '=', '0');
                })
                ->groupBy('item.id', 'cart.item_id')
                ->inRandomOrder()
                ->visibleOnMenu()
                ->take(8)->get();
        }

        $setting = Settings::first();
        $theme = $setting->theme;
        if (env('Environment') == 'sendbox') {
            if ($request->theme_id) {
                $theme = $request->theme_id;
            }
        }

        $lang = Languages::get();
        return view('web.city.home', compact('sliders', 'banners', 'todayspecial', 'topitemlist', 'storereviews', 'getteams', 'getfaqs', 'getblogs', 'getwhychooseus', 'recommended', 'topdealsproduct', 'topdeals', 'getgalleries', 'lang'));
    }
    public function categories(Request $request)
    {
        return view('web.categoryviewall');
    }
    public function menu(Request $request)
    {
        return view('web.menu');
    }
    public function change_dir(Request $request)
    {
        session()->put('direction', $request->dir);
        return redirect()->back();
    }
}
