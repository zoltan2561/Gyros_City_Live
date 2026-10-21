<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Item;
use App\Helpers\helper;
use App\Models\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session; // biztos import


class CartController extends Controller
{
    public function __construct()
    {
        if (!\Session::isStarted()) {
            \Session::start();
        }
    }


    public function index(Request $request)
    {
        // 1) Egységes kulcs: ha be van jelentkezve, user_id szerint, különben session_id
        $q = \App\Models\Cart::query()->where('buynow', 0);

        if (\Illuminate\Support\Facades\Auth::check()) {
            $q->where('user_id', \Illuminate\Support\Facades\Auth::id());
        } else {
            $q->where('session_id', \Session::getId());
        }

        $getcartlist = $q->orderByDesc('id')->get();
        $cartAvailability = Item::visibleOnMenu()->whereIn('id', $getcartlist->pluck('item_id'))->get()->keyBy('id');

        $getsettings = Settings::first();
        $tax_name = $tax_price = [];

        foreach ($getcartlist as $cart) {
            $taxlist = helper::gettax($cart->tax);
            if (!empty($taxlist)) {
                foreach ($taxlist as $tax) {
                    if (!$tax) continue;
                    $idx = array_search($tax->name, $tax_name);
                    $price = ($tax->type == 1)
                        ? ($tax->tax * $cart->qty)
                        : (($tax->tax / 100) * ($cart->addons_total_price + $cart->item_price) * $cart->qty);
                    if ($idx === false) {
                        $tax_name[]  = $tax->name;
                        $tax_price[] = $price;
                    } else {
                        $tax_price[$idx] += $price;
                    }
                }
            }
        }

        $taxArr = ['tax' => $tax_name, 'rate' => $tax_price];
        return view('web.cart.cart', compact('getcartlist', 'getsettings', 'taxArr', 'cartAvailability'));
    }

    public function addtocart(Request $request)
    {
        $request->validate(['slug' => 'required|string', 'qty' => 'required|integer|min:1|max:100', 'buynow' => 'nullable|integer|in:0,1']);
        $itemdata = Item::visibleOnMenu()->where('slug', $request->slug)->first();
        if (!$itemdata || !$itemdata->is_orderable) {
            return response()->json(['status' => 0, 'message' => 'Ez az étel jelenleg nem elérhető.', 'buynow' => (int) $request->buynow]);
        }
        try {
            // buynow mindig legyen 0/1, ne NULL
            $buynow = (int)$request->input('buynow', 0);

            // 1) töröljük az előző "buynow" sort ugyanazon kulcs alatt
            if ($buynow === 1) {
                if (Auth::check()) {
                    Cart::where('buynow', 1)->where('user_id', Auth::id())->delete();
                } else {
                    if (!Session::isStarted()) { Session::start(); }
                    Cart::where('buynow', 1)->where('session_id', Session::getId())->delete();
                }
            }

            // 2) konzisztens kulcs mentése
            $cart = new Cart();
            if (Auth::check()) {
                $cart->user_id    = Auth::id();
                // opcionálisan megtarthatod a session_id-t is log/elemzés célra
                if (!Session::isStarted()) { Session::start(); }
                $cart->session_id = Session::getId();
            } else {
                $cart->user_id    = null; // ne üres string
                if (!Session::isStarted()) { Session::start(); }
                $cart->session_id = Session::getId();
            }

            $cart->item_id            = $itemdata->id;
            $cart->item_name          = $itemdata->item_name;
            $cart->item_type          = $itemdata->item_type;
            $cart->item_image         = $itemdata->item_image?->image_name ?? 'item-placeholder.png';
            $cart->tax                = $itemdata->tax ?? '';
            $price = (float) $itemdata->price;
            if ($itemdata->is_top_deals == 1 && ($deal = helper::top_deals())) {
                $price = (int) $deal->offer_type === 1
                    ? ($price > $deal->offer_amount ? $price - $deal->offer_amount : $price)
                    : $price * (1 - $deal->offer_amount / 100);
            }
            $cart->item_price         = helper::number_format($price);
            $cart->addons_id          = $request->addons_id ?: null;
            $cart->addons_name        = $request->addons_name ?: null;
            $cart->addons_price       = $request->addons_price ?: null;
            $cart->addons_total_price = helper::number_format($request->filled('addons_price') ? array_sum(explode('| ', $request->addons_price)) : 0);
            $cart->extras_id          = $request->extras_id ?: null;
            $cart->extras_name        = $request->extras_name ?: null;
            $cart->extras_price       = $request->extras_price ?: null;
            $cart->extras_total_price = helper::number_format($request->filled('extras_price') ? array_sum(explode('| ', $request->extras_price)) : 0);
            $cart->qty                = (int)$request->qty;
            $cart->buynow             = $buynow;
            $cart->save();

            // jelképes számláló (ugyanazzal a kulccsal!)
            if (Auth::check()) {
                $total_count = Cart::where('user_id', Auth::id())->where('buynow', 0)->count();
            } else {
                $total_count = Cart::where('session_id', Session::getId())->where('buynow', 0)->count();
            }

            session()->forget('discount_data');

            return response()->json([
                'status' => 1,
                'message' => trans('messages.success'),
                'data' => $total_count,
                'total_item_count' => helper::get_item_cart($itemdata->id),
                'buynow' => $buynow
            ], 200);

        } catch (\Throwable $th) {
            report($th);
            return response()->json([
                'status' => 0,
                'message' => trans('messages.wrong'),
                'buynow' => (int)$request->input('buynow', 0)
            ], 200);
        }
    }

    public function qtyupdate(Request $request)
    {
        $request->validate([
            'id'   => 'required|integer',
            'type' => 'required|in:plus,minus',
        ]);

        if (!\Session::isStarted()) { \Session::start(); }

        // csak a SAJÁT kosár sorát engedjük módosítani
        $row = \App\Models\Cart::query()
            ->where('id', (int)$request->id)
            ->when(\Illuminate\Support\Facades\Auth::check(),
                fn($q) => $q->where('user_id', \Illuminate\Support\Facades\Auth::id()),
                fn($q) => $q->where('session_id', \Session::getId())
            )
            ->where('buynow', 0)
            ->first();

        if (!$row) {
            return response()->json(['status' => 0, 'message' => trans('messages.invalid_cart')], 404);
        }

        // teljes kosár darabszám (max_order_qty ellenőrzéshez)
        $total_count = \App\Models\Cart::query()
            ->when(\Illuminate\Support\Facades\Auth::check(),
                fn($q) => $q->where('user_id', \Illuminate\Support\Facades\Auth::id()),
                fn($q) => $q->where('session_id', \Session::getId())
            )
            ->where('buynow', 0)
            ->sum('qty');

        if ($request->type === 'plus') {
            $item = Item::visibleOnMenu()->find($row->item_id);
            if (!$item || !$item->is_orderable) {
                return response()->json(['status' => 0, 'message' => 'Ez az étel jelenleg nem elérhető.']);
            }
            if ($total_count >= helper::appdata()->max_order_qty) {
                $msg = trans('messages.order_qty_less_then') . ' : ' . helper::appdata()->max_order_qty;
                return response()->json(['status' => 2, 'message' => $msg], 200);
            }
            $row->qty += 1;
            $row->save();
        } else { // minus
            if ($row->qty <= 1) {
                $row->delete();
                session()->forget('discount_data');

                // új kosárszám
                $new_count = \App\Models\Cart::query()
                    ->when(\Illuminate\Support\Facades\Auth::check(),
                        fn($q) => $q->where('user_id', \Illuminate\Support\Facades\Auth::id()),
                        fn($q) => $q->where('session_id', \Session::getId())
                    )
                    ->where('buynow', 0)
                    ->sum('qty');

                return response()->json([
                    'status'    => 1,
                    'removed'   => true,
                    'qty'       => 0,
                    'data'      => $new_count,
                ]);
            } else {
                $row->qty -= 1;
                $row->save();
            }
        }

        // sorösszeg és új kosárszám vissza a kliensnek
        $unit = (float)$row->item_price + (float)$row->addons_total_price + (float)$row->extras_total_price;
        $row_total = $unit * (int)$row->qty;

        $new_count = \App\Models\Cart::query()
            ->when(\Illuminate\Support\Facades\Auth::check(),
                fn($q) => $q->where('user_id', \Illuminate\Support\Facades\Auth::id()),
                fn($q) => $q->where('session_id', \Session::getId())
            )
            ->where('buynow', 0)
            ->sum('qty');

        return response()->json([
            'status'        => 1,
            'removed'       => false,
            'qty'           => (int)$row->qty,
            'row_total'     => $row_total,
            'row_total_fmt' => helper::currency_format($row_total),
            'data'          => $new_count, // ha van globális számláló a fejléchez
        ]);
    }


    public function deletecartitem(Request $request, $id = null)
    {
        $id = (int)($id ?? $request->input('id'));

        $q = \App\Models\Cart::query()->where('id', $id);

        if (\Illuminate\Support\Facades\Auth::check()) {
            $q->where('user_id', \Illuminate\Support\Facades\Auth::id());
        } else {
            $q->where('session_id', \Session::getId());
        }

        $row = $q->first();
        if (!$row) {
            return response()->json(['status' => 0, 'message' => trans('messages.invalid_cart')], 404);
        }

        $row->delete();
        session()->forget('discount_data');

        // friss kosárszám
        if (\Illuminate\Support\Facades\Auth::check()) {
            $total_count = \App\Models\Cart::where('user_id', \Illuminate\Support\Facades\Auth::id())->where('buynow', 0)->count();
        } else {
            $total_count = \App\Models\Cart::where('session_id', \Session::getId())->where('buynow', 0)->count();
        }

        return response()->json(['status' => 1, 'message' => trans('messages.success'), 'data' => $total_count]);
    }


}
