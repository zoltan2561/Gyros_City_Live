<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Helpers\helper;
use App\Helpers\whatsapp_helper;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CustomStatus;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\User;
use App\Models\Payment;
use App\Models\Settings;
use App\Models\Shippingarea;
use App\Models\SystemAddons;
use App\Models\Time;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Session;
use Illuminate\Support\Facades\Auth;
use DateTime;
use Exception;
use Stripe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CheckoutController extends Controller
{

    public function index(Request $request)
    {
        session()->forget('last_url');

        if (session()->get('order_type') == 2) {
            session()->forget('addressdata');
        }
        $getsettings = Settings::first();
        if (Auth::user() && Auth::user()->type == 2) {
            $getaddresses = Address::select('id', 'user_id', 'address_type', 'address', 'landmark', 'postal_code', 'is_default', 'title')->where('user_id', Auth::user()->id)->orderbyDesc('id')->get();
            $getcartlist = Cart::where('user_id', Auth::user()->id)->where('buynow', $request->buynow)->orderByDesc('id')->get();
            $getpaymentmethods = Payment::select('id', 'unique_identifier', 'environment', 'payment_name', 'payment_type', 'currency', 'public_key', 'secret_key', 'encryption_key', 'image')->where('is_available', 1)->orderBy('reorder_id')->where('is_activate', '1')->get();
        } else {
            $getaddresses = array();
            $getcartlist = Cart::where('session_id', Session::getId())->where('buynow', $request->buynow)->orderByDesc('id')->get();
            $getpaymentmethods = Payment::select('id', 'unique_identifier', 'environment', 'payment_name', 'payment_type', 'currency', 'public_key', 'secret_key', 'encryption_key', 'image')->where('is_available', 1)->orderBy('reorder_id')->where('payment_name', '!=', 'Wallet')->where('is_activate', '1')->get();
        }
        $producttax = 0;
        $tax_name = [];
        $tax_price = [];
        foreach ($getcartlist as $cart) {
            $taxlist =  helper::gettax($cart->tax);
            if (!empty($taxlist)) {
                foreach ($taxlist as $tax) {
                    if (!empty($tax)) {
                        if (!in_array($tax->name, $tax_name)) {
                            $tax_name[] = $tax->name;

                            if ($tax->type == 1) {
                                $price = $tax->tax * $cart->qty;
                            }

                            if ($tax->type == 2) {
                                $price = ($tax->tax / 100) * ($cart->addons_total_price + $cart->item_price) * $cart->qty;
                            }
                            $tax_price[] = $price;
                        } else {
                            if ($tax->type == 1) {
                                $price = $tax->tax * $cart->qty;
                            }

                            if ($tax->type == 2) {
                                $price = ($tax->tax / 100) * ($cart->addons_total_price + $cart->item_price) * $cart->qty;
                            }
                            $tax_price[array_search($tax->name, $tax_name)] += $price;
                        }
                    }
                }
            }
        }

        $taxArr['tax'] = $tax_name;
        $taxArr['rate'] = $tax_price;
        
       


        $shippingarea = Shippingarea::orderBy('reorder_id')->get();
        if (count($getcartlist) > 0) {
            return view('web.checkout.checkout', compact('getaddresses', 'getpaymentmethods', 'getcartlist', 'taxArr', 'getsettings', 'shippingarea'));
        } else {
            return redirect()->back();
        }
    }
    public function isopenclose(Request $request)
    {
        $buynow = ($request->buynow == null || $request->buynow == 0) ? 0 : 1;

        if (@helper::appdata()->timezone != "") {
            date_default_timezone_set(helper::appdata()->timezone);
        }

        // a bolt user (ne User::first)
        $admin = \App\Models\User::whereIn('type',[1,4])->first();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => trans('messages.restaurant_closed')], 200);
        }

        // 1) ON/OFF kapcsoló
        if ((int)$admin->is_online !== 1) {
            return response()->json(['status' => 0, 'message' => trans('messages.restaurant_closed')], 200);
        }
        //TODO: átirni 10-re a zarast
        // 2) STATIKUS NYITVATARTÁS: 08:00–22:00 minden nap  ⬇⬇⬇
        $now   = Carbon::now();
        $open  = Carbon::create($now->year, $now->month, $now->day, 8, 0, 0, $now->timezone);
        $close = Carbon::create($now->year, $now->month, $now->day, 21, 30, 0, $now->timezone);

        // nyitva: 08:00-tól 22:00-ig (21:30-kor már zárva)
        if (!($now->greaterThanOrEqualTo($open) && $now->lessThan($close))) {
            return response()->json(['status' => 0, 'message' => trans('messages.restaurant_closed')], 200);
        }
        // 2) ⬆⬆⬆

        // --- a te meglévő ellenőrzéseid maradhatnak, változtatás nélkül ---
        // kosár, min/max stb...
        if (auth()->check() && auth()->user()->type == 2) {
            $cartdata = \App\Models\Cart::where('user_id', auth()->id())->where('buynow', $buynow)->get();
        } else {
            $cartdata = \App\Models\Cart::where('session_id', \Session::getId())->where('buynow', $buynow)->get();
        }

        if ($request->qty > helper::appdata()->max_order_qty) {
            $msg = trans('messages.order_qty_less_then') . ' : ' . helper::appdata()->max_order_qty;
            return response()->json(['status' => 2, 'message' => $msg], 200);
        } elseif ($cartdata->count() <= 0) {
            return response()->json(['status' => 2, 'message' => trans('messages.cart_is_empty')], 200);
        } elseif ($request->order_amount < helper::appdata()->min_order_amount || $request->order_amount > helper::appdata()->max_order_amount) {
            $msg = trans('messages.order_amount_must_between') . ' ' . helper::currency_format(helper::appdata()->min_order_amount) . ' and ' . helper::currency_format(helper::appdata()->max_order_amount);
            return response()->json(['status' => 2, 'message' => $msg], 200);
        } else {
            if (@helper::checkaddons('customer_login')) {
                if (auth()->check() && auth()->user()->type == 2) {
                    return response()->json(['status' => 3, 'message' => trans('messages.success')], 200);
                } else {
                    if (helper::appdata()->login_required == 1) {
                        if (helper::appdata()->is_checkout_login_required == 1) {
                            return response()->json(['status' => 4, 'message' => trans('messages.success')], 200);
                        } else {
                            return response()->json(['status' => 1, 'message' => trans('messages.success')], 200);
                        }
                    } else {
                        return response()->json(['status' => 3, 'message' => trans('messages.success')], 200);
                    }
                }
            } else {
                return response()->json(['status' => 3, 'message' => trans('messages.success')], 200);
            }
        }
    }



    public function placeorder(Request $request)
    {


        try {



            // === Kötelező (csak kiszállításnál): CÍM + VÁROS ===========================
            if ((int)$request->input('order_type') === 1) {

                // kis normalizálás (felesleges szóközök eltüntetése)
                $addressNorm = preg_replace('/\s+/u', ' ', trim((string)$request->input('address')));
                $cityNorm    = preg_replace('/\s+/u', ' ', trim((string)$request->input('city')));
                $request->merge(['address' => $addressNorm, 'city' => $cityNorm]);

                $validator = \Validator::make($request->all(), [
                    // address-ben legyen legalább egy szám (házszám)
                    'address' => ['required','string','min:5','max:255','regex:/\d+/'],
                    'city'    => ['required','string','min:2','max:100'],
                ], [
                    'address.required' => 'A cím megadása kötelező.',
                    'address.regex'    => 'Kérjük, a házszámot is adja meg a címben (pl. "Petőfi u. 26").',
                    'city.required'    => 'A város megadása kötelező.',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status'  => 0,
                        'code'    => 'validation_error',
                        'message' => $validator->errors()->first(),
                        'errors'  => $validator->errors(),
                    ], 200);
                }
            }
// ===========================================================================


            if ($request->transaction_type == 1 || $request->transaction_type == 2 || $request->transaction_type == 3 || $request->transaction_type == 4 || $request->transaction_type == 5 || $request->transaction_type == 6) {

                $address = $request->address;
                $address_type = $request->address_type;
                $landmark = $request->landmark;
                $postal_code = $request->pincode;
                $delivery_charge = $request->delivery_charge;
                $name = $request->name;
                $email = $request->email;
                $mobile = $request->mobile;
                $order_type = $request->order_type;
                $transaction_type = $request->transaction_type;
                $grand_total = $request->grand_total;
                $tax = $request->tax;
                $tax_name = $request->tax_name;
                $order_notes = $request->order_notes;
                $transaction_id = $request->transaction_id;
                $country = $request->country;
                $state = $request->state;
                $city = $request->city;
                $buynow = $request->buynow;
                $delivery_date = $request->delivery_date;
                $delivery_time = $request->delivery_time;
            } else {
                $userdata = Session::get('userdata');
                $address = $userdata['address'];
                $address_type = $userdata['address_type'];
                $delivery_charge = $userdata['delivery_charge'];
                $name = $userdata['name'];
                $email = $userdata['email'];
                $mobile = $userdata['mobile'];
                $order_type = $userdata['order_type'];
                $transaction_type = $userdata['transaction_type'];
                $grand_total = $userdata['grand_total'];
                $tax_name = $userdata['tax_name'];
                $tax = $userdata['tax'];
                $order_notes = $userdata['order_notes'];
                $landmark = $userdata['landmark'];
                $postal_code = $userdata['pincode'];
                if ($request->paymentId == null) {
                    $transaction_id = session()->get('payment_id');
                } else {
                    $transaction_id = $request->paymentId;
                }

                $country = $userdata['country'];
                $state = $userdata['state'];
                $city = $userdata['city'];
                $buynow = $userdata['buynow'];
                $delivery_date = $userdata['delivery_date'];
                $delivery_time = $userdata['delivery_time'];
            }

            date_default_timezone_set(helper::appdata()->timezone);

            if (Auth::user() && Auth::user()->type == 2) {
                $cartdata = Cart::where('user_id', Auth::user()->id)->where('buynow', $buynow)->get();
            } else {
                $cartdata = Cart::where('session_id', Session::getId())->where('buynow', $buynow)->get();
            }

            if (count($cartdata) <= 0) {
                return response()->json(['status' => 0, 'message' => trans('messages.cart_is_empty1')], 200);
            }

            if ($order_type == "") {
                return response()->json(['status' => 0, 'message' => trans('messages.order_type_required')], 200);
            }
            if ($transaction_type == "") {
                return response()->json(['status' => 0, 'message' => trans('messages.transaction_type_required')], 200);
            }
            if ($transaction_type != 1 && $transaction_type != 2 && $transaction_type != 4) {
                if ($transaction_id == "") {
                    return response()->json(['status' => 0, 'message' => trans('messages.transaction_id_required')], 200);
                }
            }
            $transaction_id = $transaction_id;


            /* === MIN. RENDELÉSI ÖSSZEG CHECK (csak kiszállításnál, VÉGÖSSZEGBŐL) ===== */
            if ((int)$order_type === 1) {
                // kis helper a "340.00HUF", "5 000", "5,000.00" stb. parse-olására
                $toFloat = function($val): float {
                    if (is_null($val)) return 0.0;
                    if (is_numeric($val)) return (float)$val;
                    $s = preg_replace('/[^\d.,]/u', '', (string)$val);
                    if ($s === '' || $s === null) return 0.0;
                    if (strpos($s, ',') !== false && strpos($s, '.') === false) {
                        $s = str_replace(',', '.', $s);   // "5 000,00" -> "5000.00"
                    } else {
                        $s = str_replace(',', '', $s);    // "5,000.00" -> "5000.00"
                    }
                    return is_numeric($s) ? (float)$s : 0.0;
                };

                // 1) Szállítási díj → zóna → min rendelés
                $dc = (int) round($toFloat($delivery_charge));
                 if ($dc <= 560) {
                    $minRequired = 0; // vagy amit szeretnél
                } elseif ($dc <= 760) {
                    $minRequired = 5900;
                } elseif ($dc <= 2200) {
                    $minRequired = 7900;
                } else {
                    $minRequired = 11900;
                }

                // 2) VÉGÖSSZEG (grand_total) a limithez
                //$gt = (int) round($toFloat($grand_total));             // pl. 5000
                // Ha inkább a szállítás NÉLKÜLI összeggel hasonlítanál, ezt használd:
                 $gt = max(0, (int) round($toFloat($grand_total)) - (int) round($toFloat($delivery_charge)));

                if ($gt < $minRequired) {
                    $missing = $minRequired - $gt;
                    return response()->json([
                        'status'  => 0,
                        'code'    => 'min_order_not_met',
                        'message' => 'Nincs meg a minimum rendelési összeg: '.$minRequired.' Ft. '
                            . 'Jelenlegi (végösszeg): '.$gt.' Ft. Hiányzik: '.$missing.' Ft.',
                    ], 200);
                }
            }
            /* ======================================================================== */










            if (Auth::user() && Auth::user()->type == 2) {
                $checkuser = User::where('is_available', 1)->where('id', Auth::user()->id)->first();
                if ($transaction_type == 2) {
                    if ($checkuser->wallet == "" || ($checkuser->wallet < $grand_total)) {
                        return response()->json(['status' => 0, 'message' => trans('messages.insufficient_wallet')], 200);
                    }
                }
            }

            $defaultsatus = CustomStatus::where('type', 1)->where('order_type', $order_type)->where('is_available', 1)->where('is_deleted', 2)->first();
            if (empty($defaultsatus) && $defaultsatus == null) {
                if ($transaction_type == 7 || $transaction_type == 8 || $transaction_type == 9 || $transaction_type == 10 || $transaction_type == 11 || $transaction_type == 12 || $transaction_type == 13 || $transaction_type == 14) {
                    return redirect()->back()->with('error', trans('order not placed without default status !!'));
                } else {
                    return response()->json(['status' => 0, 'message' => trans('order not placed without default status !!')], 200);
                }
            }

            if ($transaction_type == 4) {
                try {
                    $stripekey = helper::stripe_data()->secret_key;

                    Stripe\Stripe::setApiKey($stripekey);
                    $token = $transaction_id;

                    $charge = Stripe\Charge::create([
                        'amount' => round($grand_total * 100),
                        'currency' => helper::stripe_data()->currency,
                        "description" => "SingleGrocery-OrderPayment",
                        'source' => $token,
                    ]);
                    $transaction_id = $charge->id;
                } catch (Exception $e) {
                    dd($e);
                    return response()->json(['status' => 0, 'message' => trans('messages.unable_to_complete_payment')], 200);
                }
            }

            $getordernumber = Order::select('order_number', 'order_number_digit', 'order_number_start')->orderBy('id', 'DESC')->first();

            if (empty($getordernumber->order_number_digit)) {
                $n = helper::appdata()->order_number_start;
                $newbooking_number = str_pad($n, 0, STR_PAD_LEFT);
            } else {
                if ($getordernumber->order_number_start == helper::appdata()->order_number_start) {
                    $n = (int)($getordernumber->order_number_digit);
                    $newbooking_number = str_pad($n + 1, 0, STR_PAD_LEFT);
                } else {
                    $n = helper::appdata()->order_number_start;
                    $newbooking_number = str_pad($n, 0, STR_PAD_LEFT);
                }
            }
            $order = new Order;
            $order_number = helper::appdata()->order_prefix . $newbooking_number;
            $order->order_number = $order_number;
            $order->order_number_digit = $newbooking_number;
            $order->order_number_start = helper::appdata()->order_number_start;
            $order->user_id = @$checkuser->id;
            $order->order_type = $order_type;

            if ($order_type == 1) {
                $order->address_type = $address_type;
                $order->address = $address;
                $order->landmark = $landmark;
                $order->postal_code = $postal_code;
                $order->country = $country;
                $order->state = $state;
                $order->city = $city;
            } else {
                $order->address_type = null;
                $order->address = null;
                $order->landmark = null;
                $order->postal_code = null;
                $order->country = null;
                $order->state = null;
                $order->city = null;
            }

            $order->name = $name;
            $order->email = $email;
            $order->mobile = $mobile;
            if (session()->has('discount_data')) {
                $order->offer_code = session()->get('discount_data')['offer_code'];
                $order->discount_amount = helper::number_format(session()->get('discount_data')['offer_amount']);
            } else {
                $order->offer_code = "";
                $order->discount_amount = helper::number_format(0);
            }
            $order->transaction_type = $transaction_type;
            if ($transaction_type != 1 && $transaction_type != 2 ) {
                $order->transaction_id = $transaction_id;
            }
            $order->tax_amount = $tax;
            $order->tax_name = $tax_name;
            $order->delivery_charge = helper::number_format($delivery_charge);
            $order->grand_total = helper::number_format($grand_total);
            $order->order_notes = $order_notes;
            $order->order_from = "web";
            $order->status = $defaultsatus->id;
            $order->status_type = $defaultsatus->type;
            $order->delivery_date = $delivery_date;
            $order->delivery_time = $delivery_time;
            if ($transaction_type  == 1) {
                $order->payment_status = 1;
            } else {
                $order->payment_status = 2;
            }

            if ($order->save()) {
                if ($transaction_type == 2) {
                    $checkuser->wallet = $checkuser->wallet - $grand_total;
                    $transaction = new Transaction();
                    $transaction->user_id = @$checkuser->id;
                    $transaction->order_id = $order->id;
                    $transaction->order_number = $order_number;
                    $transaction->transaction_id = $transaction_id;
                    $transaction->transaction_type = 1;
                    $transaction->amount = helper::number_format($grand_total);
                    if ($transaction->save()) {
                        $checkuser->save();
                    }
                }
                if (Auth::user() && Auth::user()->type == 2) {
                    $cartdata = Cart::where('user_id', $checkuser->id)->where('buynow', $buynow)->get();
                } else {
                    $cartdata = Cart::where('session_id', Session::getId())->where('buynow', $buynow)->get();
                }

                foreach ($cartdata as $cart) {
                    $od = new OrderDetails();
                    $od->order_id = $order->id;
                    $od->user_id = @$checkuser->id;
                    $od->item_id = $cart->item_id;
                    $od->item_name = $cart->item_name;
                    $od->item_type = $cart->item_type;
                    $od->item_image = $cart->item_image;
                    $od->tax = $cart->tax;
                    $od->qty = $cart->qty;
                    $od->item_price = $cart->item_price;
                    $od->addons_id = $cart->addons_id;
                    $od->addons_name = $cart->addons_name;
                    $od->addons_price = $cart->addons_price;
                    $od->addons_total_price = $cart->addons_total_price;
                    $od->extras_id = $cart->extras_id;
                    $od->extras_name = $cart->extras_name;
                    $od->extras_price = $cart->extras_price;
                    $od->extras_total_price = $cart->extras_total_price;
                    $od->save();
                }

                if (Auth::user() && Auth::user()->type == 2) {
                    Cart::where('user_id', $checkuser->id)->where('buynow', $buynow)->delete();
                    if ($checkuser->is_notification == 1) {
                        $title = trans('labels.order_placed');
                        $body = "Your Order " . $order_number . " has been placed.";
                        $noti = helper::push_notification($checkuser->token, $title, $body, "order", $order->id);
                    }
                    $orderdata = Order::where('id', $order->id)->first();
                    $itemdata = OrderDetails::where('order_id', $order->id)->get();
                    if ($checkuser->is_mail == 1) {
                        $invoice_helper = helper::create_order_invoice($checkuser->email, $checkuser->name, $order_number, $orderdata, $itemdata);
                    }
                } else {
                    Cart::where('session_id', Session::getId())->where('buynow', $buynow)->delete();

                    $title = trans('labels.order_placed');
                    $body = "Your Order " . $order_number . " has been placed.";
                    $noti = helper::push_notification(125, $title, $body, "order", $order->id);

                    $orderdata = Order::where('id', $order->id)->first();
                    $itemdata = OrderDetails::where('order_id', $order->id)->get();
                }



                $admindata = User::select('id', 'name', 'email', 'mobile')->where('type', 1)->first();
                if (Auth::user() && Auth::user()->type == 2) {
                    $admin_invoice = helper::create_order_invoice($admindata->email, $checkuser->name, $order_number, $orderdata, $itemdata);
                } else {
                    $admin_invoice = helper::create_order_invoice($admindata->email, $request->name, $order_number, $orderdata, $itemdata);
                }
                session()->forget('discount_data');
                session()->forget('userdata');

                if ($transaction_type == 7 || $transaction_type == 8 || $transaction_type == 9 || $transaction_type == 10 || $transaction_type == 11 || $transaction_type == 12 || $transaction_type == 13 || $transaction_type == 14) {
                    return redirect('/success-' . $order_number)->with('success', trans('messages.order_placed_note'));
                }
                if (@helper::checkaddons('whatsapp_message')) {
                    if (whatsapp_helper::whatsapp_message_config()->order_created == 1) {
                        whatsapp_helper::whatsappmessage($order_number);
                    }
                }


                // === HŰSÉG BÓNUSZ: 50 Ft / 1000 Ft (csak bejelentkezett usernek, egyszer / rendelés) ===
                if (Auth::check()) {
                    $user = Auth::user();

                    // grand_total a rendelésen (HUF). 
                    //TODO: élesre
                    $grandTotalFt = (int) round($order->grand_total);
                    $bonus = (int) (floor($grandTotalFt / 1000) * 0);

                    if ($bonus > 0) {
                        // Cache alapú idempotencia: ha már jóváírtuk ennél az order_id-nél, ne írjuk jóvá újra
                        $cacheKey = 'loyalty_bonus_applied_order_' . $order->id;

                        if (\Illuminate\Support\Facades\Cache::add($cacheKey, 1, now()->addDays(30))) {
                            // atomi növelés: wallet = wallet + bonus
                            \Illuminate\Support\Facades\DB::table('users')
                                ->where('id', $user->id)
                                ->update([
                                    'wallet' => \Illuminate\Support\Facades\DB::raw('wallet + ' . (int)$bonus)
                                ]);
                        }
                    }
                }

                return response()->json(['status' => 1, 'message' => trans('messages.success'), 'order_id' => $order_number], 200);
            } else {
                return response()->json(['status' => 0, 'message' => trans('messages.wrong')], 200);
            }




        } catch (\Throwable $th) {
            dd($th);
            return response()->json(['status' => 0, 'message' => trans('messages.wrong')], 200);
        }
    }

    public function timeslot(Request $request)
    {
        try {
            $slots = [];
            date_default_timezone_set(helper::appdata()->timezone);

            if ($request->inputDate != "" || $request->inputDate != null) {
                $day = date('l', strtotime(helper::date_format($request->inputDate)));

                $minute = "";
                $time = Time::where('day', $day)->first();

                if ($time->always_close == 1) {
                    $slots = "1";
                } else {
                    if (helper::appdata()->interval_type == 2) {
                        $minute = (float)helper::appdata()->interval_time * 60;
                    }
                    if (helper::appdata()->interval_type == 1) {
                        $minute = helper::appdata()->interval_time;
                    }

                    $firsthalf = new CarbonPeriod(date("H:i", strtotime($time->open_time)), $minute . ' minutes', date("H:i", strtotime($time->break_start))); // for create use 24 hours format later change format

                    $secondhalf =  new CarbonPeriod(date("H:i", strtotime($time->break_end)), $minute . ' minutes', date("H:i", strtotime($time->close_time)));

                    foreach ($firsthalf as $item) {
                        $starttime[] = helper::time_format($item);
                    }
                    foreach ($secondhalf as $item) {
                        $endtime[] = helper::time_format($item);
                    }

                    for ($i = 0; $i < count($starttime) - 1; $i++) {
                        $temparray[] = $starttime[$i] . ' ' . '-' . ' ' . next($starttime);
                    }
                    for ($i = 0; $i < count($endtime) - 1; $i++) {
                        $temparray[] = $endtime[$i] . ' ' . '-' . ' ' . next($endtime);
                    }

                    $currenttime = Carbon::now()->format('H:i');
                    $current_date = helper::date_format(Carbon::now());

                    foreach ($temparray as $item) {
                        $ordercount = Order::where('delivery_date', $request->inputDate)->where('delivery_time', $item)->count();
                        if ($ordercount < helper::appdata()->perslot_booking_limit) {
                            $slot_parts = explode(' - ', $item);
                            if ($request->inputDate === $current_date) {
                                if ($currenttime < date('H:i', strtotime($slot_parts[1]))) {
                                    $slots[] = array(
                                        'slot' => $item,
                                    );
                                }
                            } else {
                                $slots[] = array(
                                    'slot' => $item,
                                );
                            }
                        }
                    }
                }
            }
            return $slots;
        } catch (\Throwable $th) {

            return response()->json(['status' => 0, 'message' => trans('messages.wrong')], 200);
        }
    }

    public function paymentsuccess(Request $request)
    {
        try {
            if ($request->has('paymentId')) {
                $paymentId = request('paymentId');
                $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
            }
            if ($request->has('payment_id')) {
                $paymentId = request('payment_id');
                $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
            }

            if ($request->has('transaction_id')) {
                $paymentId = request('transaction_id');
                $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
            }

            if (Session::get('payment_type') == "11") {
                $checkstatus = app('App\Http\Controllers\addons\PayTabController')->checkpaymentstatus(Session::get('tran_ref'));
                if ($checkstatus == "A") {
                    $paymentId = Session::get('tran_ref');
                    $response = ['status' => '1', 'msg' => 'paid', 'paymentId' => $paymentId];
                } else {
                    return redirect('/checkout?buynow='.Session::get('buynow'))->with('error', trans('messages.unable_to_complete_payment'));
                }
            }

            if (Session::get('payment_type') == "12") {
                if ($request->code == "PAYMENT_SUCCESS") {
                    $paymentId = $request->transactionId;
                    $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
                } else {
                    return redirect('/checkout?buynow='.Session::get('buynow'))->with('error', trans('messages.unable_to_complete_payment'));
                }
            }

            if (Session::get('payment_type') == "13") {
                $checkstatus = app('App\Http\Controllers\addons\MollieController')->checkpaymentstatus(Session::get('tran_ref'));

                if ($checkstatus == "A") {
                    $paymentId = Session::get('tran_ref');
                    $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
                } else {
                    return redirect('/checkout?buynow='.Session::get('buynow'))->with('error', trans('messages.unable_to_complete_payment'));
                }
            }

            if (Session::get('payment_type') == "14") {

                if ($request->status == "Completed") {
                    $paymentId = $request->transaction_id;
                    $response = ['status' => 1, 'msg' => 'paid', 'paymentId' => $paymentId];
                } else {
                    return redirect('/checkout?buynow='.Session::get('buynow'))->with('error', trans('messages.unable_to_complete_payment'));
                }
            }
        } catch (\Exception $e) {
            $response = ['status' => 0, 'msg' => $e->getMessage()];
        }

        $request = new Request($response);

        return $this->placeorder($request);
    }

    public function paymentfail()
    {
        if (count(request()->all()) > 0) {
            return redirect('/checkout?buynow='.Session::get('buynow'))->with('error', trans('messages.unable_to_complete_payment'));
        } else {
            return redirect('/checkout?buynow='.Session::get('buynow'));
        }
    }


    //uj hűségpont logika
    private function computeLoyaltyBonusFt(int $grandTotalFt): int
    {
        if ($grandTotalFt <= 0) return 0;
        return (int) (floor($grandTotalFt / 1000) * 50);
    }


    public function barionPing(Request $request)
    {
        return response()->json([
            'ok'  => true,
            'msg' => 'működik a hívás (Barion 16 teszt)'
        ], 200);
    }

}
