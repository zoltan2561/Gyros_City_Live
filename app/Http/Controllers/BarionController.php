<?php

namespace App\Http\Controllers;

use App\Services\BarionService;
use App\Services\DeliveryMinimum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\Shippingarea;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\CustomStatus;
use App\Models\User;
use App\Models\BarionTransaction;
use App\Helpers\helper;

class BarionController extends Controller
{
    /**
     * Barion indítás – NINCS rendelés létrehozás itt!
     * A rendelés csak a callback-ben készül el, ha a fizetés Succeeded.
     */
    public function start(Request $request)
    {
        try {
            $buynow = (int)($request->input('buynow') ?? 0);

            // kosár beolvasás
            if (auth()->check() && auth()->user()->type == 2) {
                $userId   = auth()->id();
                $cartdata = Cart::where('user_id', $userId)->where('buynow', $buynow)->get();
            } else {
                $userId   = null;
                $cartdata = Cart::where('session_id', Session::getId())->where('buynow', $buynow)->get();
            }
            if ($cartdata->count() <= 0) {
                return response()->json(['ok' => false, 'msg' => trans('messages.cart_is_empty1')], 200);
            }

            $orderType = (int) $request->input('order_type', 1);
            if (!in_array($orderType, [1, 2], true)) {
                return response()->json(['ok' => false, 'code' => 'invalid_order_type', 'msg' => 'Válassz átvételi módot.'], 200);
            }

            // The posted hidden charge can be stale. Resolve the selected area on the server.
            $deliveryCharge = 0;
            if ($orderType === 1) {
                $areaId = $request->input('delivery_area');
                $area = ctype_digit((string) $areaId) ? Shippingarea::find($areaId) : null;
                if (!$area) {
                    return response()->json([
                        'ok' => false,
                        'code' => 'invalid_delivery_area',
                        'msg' => 'Válaszd ki a szállítási területet. Ha már kiválasztottad, frissítsd az oldalt és próbáld újra.',
                    ], 200);
                }
                $deliveryCharge = $area->delivery_charge;
            }

            // Reject before creating a payment or a draft, using the same rule as placeorder.
            $minimumError = DeliveryMinimum::violation(
                $orderType,
                $request->input('grand_total', '0'),
                $deliveryCharge
            );
            if ($minimumError !== null) {
                return response()->json([
                    'ok' => false,
                    'code' => $minimumError['code'],
                    'msg' => $minimumError['message'],
                ], 200);
            }

            // draft (amit a callback-ben véglegesítünk)
            $draft = [
                'order_type'       => $orderType,
                'transaction_type' => 16, // Barion
                'grand_total'      => (string)$request->input('grand_total','0'),
                'tax'              => (string)$request->input('tax',''),
                'tax_name'         => (string)$request->input('tax_name',''),
                'delivery_charge'  => (string)$deliveryCharge,

                'name'   => trim(($request->input('first_name','').' '.$request->input('last_name',''))),
                'email'  => (string)$request->input('email',''),
                'mobile' => (string)$request->input('mobile',''),

                'address'      => (string)$request->input('address',''),
                'city'         => (string)$request->input('city',''),
                'landmark'     => (string)$request->input('landmark',''),
                'postal_code'  => (string)$request->input('pincode',''),
                'country'      => (string)$request->input('country',''),
                'state'        => (string)$request->input('state',''),

                'order_notes'  => (string)$request->input('order_notes',''),
                'delivery_date'=> (string)$request->input('delivery_date',''),
                'delivery_time'=> (string)$request->input('delivery_time',''),

                'discount'     => session()->has('discount_data') ? session('discount_data') : null,

                'items'        => $cartdata->map(function($c){
                    return [
                        'item_id' => $c->item_id,
                        'item_name' => $c->item_name,
                        'item_type' => $c->item_type,
                        'item_image'=> $c->item_image,
                        'tax'      => $c->tax,
                        'qty'      => $c->qty,
                        'item_price'=> $c->item_price,
                        'addons_id' => $c->addons_id,
                        'addons_name'=> $c->addons_name,
                        'addons_price'=> $c->addons_price,
                        'addons_total_price'=> $c->addons_total_price,
                        'extras_id' => $c->extras_id,
                        'extras_name'=> $c->extras_name,
                        'extras_price'=> $c->extras_price,
                        'extras_total_price'=> $c->extras_total_price,
                    ];
                })->values()->all(),

                'user_id'    => $userId,
                'session_id' => Session::getId(),
                'buynow'     => $buynow,
            ];

            // Barion service meghívása – domainen fog működni, nincs localhost hardcode
            $env    = app()->environment('prod') ? 'prod' : 'test';
            /** @var BarionService $barion */
            $barion = app(BarionService::class, ['env' => $env]);

            // ideiglenes azonosító a Barion kéréshez (NEM a végső order ID)
            $tmpOrderId  = time();
            $payerEmail  = (string)$draft['email'];

            // kosár átkonvertálása a service-nek
            $svcCart = [];
            foreach ($draft['items'] as $it) {
                $svcCart[] = [
                    'name'  => $it['item_name'],
                    'qty'   => (int)$it['qty'],
                    'price' => (float)$it['item_price'],
                    'sku'   => (string)$it['item_id'],
                ];
            }
            $ship = (float)($draft['delivery_charge'] ?? 0);
            if ($ship > 0) {
                $svcCart[] = ['name'=>'Szállítás','qty'=>1,'price'=>$ship,'sku'=>'shipping'];
            }

            // végösszeg (HUF)
            $grandTotalF = (float)preg_replace('/[^\d.]/','', (string)$draft['grand_total']);

            $res = $barion->startPayment($tmpOrderId, $payerEmail, $svcCart, $grandTotalF);
            // $res = ['paymentId' => ..., 'gatewayUrl' => ..., 'status' => ...]

            // draft eltárolása a barion_transactions táblában a payment_id-hez
            $tx = BarionTransaction::where('order_id', $tmpOrderId)->latest('id')->first();
            if ($tx) {
                $tx->payment_id = $res['paymentId'];
                $tx->draft_json = $draft;   // JSON castolt mező
                $tx->save();
            }

            Session::put('payment_type','16');
            Session::put('buynow', $buynow);

            return response()->json([
                'ok'         => true,
                'payment_id' => $res['paymentId'],
                'redirect'   => $res['gatewayUrl'], // frontend ide navigál
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Barion start error: '.$e->getMessage());
            return response()->json(['ok'=>false,'msg'=>'Barion indítás sikertelen'],200);
        }
    }

    /**
     * Barion visszairányítás (a vásárló böngészője jön ide).
     * Ha a callback már lefutott, azonnal megyünk a success-<ORDERNO>-ra.
     */
    public function after(Request $request)
    {
        $paymentId = $request->query('paymentId')
            ?? $request->query('PaymentId')
            ?? $request->input('paymentId')
            ?? $request->input('PaymentId');
        if (!$paymentId) {
            return redirect('/checkout?buynow='.session('buynow',0))
                ->with('error', 'Hiányzó paymentId.');
        }

        // ha a callback már létrehozta az ordert, itt lesz eltett success URL
        $successUrl = Cache::pull('barion_success_url_'.$paymentId);
        if ($successUrl) {
            return redirect($successUrl)->with('success', trans('Sikeres Fizetés!Rendelésed feldolgozás alatt. Köszönjük!'));
        }

        // Biztonsági rásegítés: állapot lekérés + callback lefuttatása idempotensen
        /** @var BarionService $barion */
        $barion = app(BarionService::class);
        $state  = $barion->fetchState($paymentId);
        $succ   = (string)($state->Status?->value ?? $state->Status ?? '') === 'Succeeded';

        if ($succ) {
            $barion->handleCallback($paymentId); // idempotens
            $successUrl = Cache::pull('barion_success_url_'.$paymentId);
            if ($successUrl) {
                return redirect($successUrl)->with('success', trans('Sikeres Fizetés!Rendelésed feldolgozás alatt. Köszönjük!'));
            }
        }

        // még nincs kész → vissza a checkoutra
        return redirect('/checkout?buynow='.session('buynow',0))
            ->with('error', trans('Hiba történt a fizetés feldolgozása során. Kérjük, próbáld újra!'));
    }

    /**
     * Barion IPN / callback (a Barion szerver hívja). Itt hozzuk létre a rendelést.
     */
    public function callback(\Illuminate\Http\Request $request)
    {
        // 1) bejövő naplózása (ne dobjon hibát log hiba esetén)
        try {
            \App\Models\PaymentLog::create([
                'payment_id' => $request->input('PaymentId') ?? $request->input('paymentId') ?? 'N/A',
                'event'      => 'CALLBACK_HIT',
                'message'    => json_encode([
                    'query'   => $request->query(),
                    'input'   => $request->all(),
                    'headers' => $request->headers->all(),
                    'ip'      => $request->ip(),
                    'ua'      => $request->header('User-Agent'),
                ], JSON_UNESCAPED_UNICODE)
            ]);
        } catch (\Throwable $e) {
            // swallow
        }

        // 2) paymentId kinyerése több forrásból/néven
        $paymentId = $request->input('PaymentId')
            ?? $request->input('paymentId')
            ?? $request->input('Id')
            ?? $request->input('id')
            ?? $request->query('PaymentId')
            ?? $request->query('paymentId')
            ?? $request->query('Id')
            ?? $request->query('id');

        if (!$paymentId) {
            \Log::warning('Barion callback without paymentId');
            \App\Models\PaymentLog::create([
                'payment_id' => 'N/A',
                'event'      => 'CALLBACK',
                'message'    => 'Missing PaymentId'
            ]);
            return response('OK', 200);
        }

        // 3) állapot lekérdezése a Bariontól
        try {
            /** @var \App\Services\BarionService $barion */
            $barion = app(\App\Services\BarionService::class);
            $state  = $barion->fetchState($paymentId);
        } catch (\Throwable $e) {
            \Log::error("Barion PaymentState error for {$paymentId}: ".$e->getMessage());
            \App\Models\PaymentLog::create([
                'payment_id' => $paymentId,
                'event'      => 'CALLBACK_ERROR',
                'message'    => 'PaymentState exception: '.$e->getMessage()
            ]);
            return response('OK', 200);
        }

        // 4) státusz normalizálása (objektum/enum -> string)
        $statusRaw = $state->Status ?? '';
        if (is_object($statusRaw)) {
            if (property_exists($statusRaw, 'value')) $statusRaw = $statusRaw->value;
            elseif (method_exists($statusRaw, '__toString')) $statusRaw = (string)$statusRaw;
            else $statusRaw = get_class($statusRaw);
        }
        $status = strtolower((string)$statusRaw);

        // engedjük az "authorized" állapotot is, ha így fizetsz
        $isOk = in_array($status, ['succeeded','completed','authorized'], true);

        // 5) tranzakció + rendelés kezelése adatbázis tranzakcióban
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($paymentId, $state, $status, $isOk) {
                /** @var \App\Models\BarionTransaction|null $txRow */
                $txRow = \App\Models\BarionTransaction::where('payment_id', $paymentId)->lockForUpdate()->first();

                if (!$txRow) {
                    \App\Models\PaymentLog::create([
                        'payment_id' => $paymentId,
                        'event'      => 'CALLBACK',
                        'message'    => 'Unknown paymentId'
                    ]);
                    return;
                }

                // mindig mentsük el, hogy elért minket a Barion
                $txRow->raw_response      = json_encode($state, JSON_UNESCAPED_UNICODE);
                $txRow->last_callback_at  = now();

                // státusz frissítése, ha változott
                $map = [
                    'succeeded' => 'Succeeded',
                    'completed' => 'Succeeded',
                    'authorized'=> 'Authorized',
                    'canceled'  => 'Cancelled',
                    'cancelled' => 'Cancelled',
                    'failed'    => 'Failed',
                    'expired'   => 'Expired',
                    'prepared'  => 'Prepared',
                ];
                $newStatus = $map[$status] ?? ($status ?: $txRow->status);

                if ($newStatus !== $txRow->status) {
                    $txRow->status = $newStatus;
                    \App\Models\PaymentLog::create([
                        'payment_id' => $paymentId,
                        'event'      => 'CALLBACK',
                        'message'    => "Status -> {$newStatus}"
                    ]);
                } else {
                    \App\Models\PaymentLog::create([
                        'payment_id' => $paymentId,
                        'event'      => 'CALLBACK',
                        'message'    => "No change ({$txRow->status})"
                    ]);
                }
                $txRow->save();

                // csak sikeres/authorized esetén építünk rendelést
                if (!$isOk) {
                    return;
                }

                // idempotencia: ha már készült rendelés ezzel a tranzakcióval, lépjünk ki
                $existing = \App\Models\Order::where('transaction_id', $paymentId)->first();
                if ($existing) {
                    return;
                }

                // drafthoz ragaszkodunk (a te logikád szerint innen állítjuk össze a rendelést)
                $draft = $txRow->draft_json ?: null;
                if (!$draft || !is_array($draft)) {
                    // ha JSON string lenne, próbáljuk dekódolni:
                    if (is_string($txRow->draft_json)) {
                        $decoded = json_decode($txRow->draft_json, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $draft = $decoded;
                        }
                    }
                }
                if (!$draft || !is_array($draft)) {
                    \App\Models\PaymentLog::create([
                        'payment_id' => $paymentId,
                        'event'      => 'CALLBACK',
                        'message'    => 'Missing or invalid draft'
                    ]);
                    return;
                }

                // kötelező default státusz
                $defaults = \App\Models\CustomStatus::where('type', 1)
                    ->where('order_type', (int)($draft['order_type'] ?? 1))
                    ->where('is_available', 1)
                    ->where('is_deleted', 2)
                    ->firstOrFail();

                // sorszám generálás (javítva: helyes str_pad szélesség)
                $last = \App\Models\Order::select('order_number_digit','order_number_start')->orderByDesc('id')->first();
                $start = (int)(\helper::appdata()->order_number_start);
                $width = max(1, strlen((string)$start));
                if (empty($last?->order_number_digit)) {
                    $n   = $start;
                    $num = str_pad((string)$n, $width, '0', STR_PAD_LEFT);
                } else {
                    $n   = ($last->order_number_start == $start) ? ((int)$last->order_number_digit + 1) : $start;
                    $num = str_pad((string)$n, $width, '0', STR_PAD_LEFT);
                }
                $order_number = \helper::appdata()->order_prefix . $num;

                // user (ha van)
                $checkuser = !empty($draft['user_id']) ? \App\Models\User::find($draft['user_id']) : null;

                // rendelés mentés
                $o = new \App\Models\Order();
                $o->order_number        = $order_number;
                $o->order_number_digit  = $num;
                $o->order_number_start  = $start;
                $o->user_id             = $checkuser?->id;
                $o->order_type          = (int)($draft['order_type'] ?? 1);

                if ($o->order_type === 1) {
                    $o->address_type  = $draft['address_type'] ?? null;
                    $o->address       = $draft['address'] ?? null;
                    $o->landmark      = $draft['landmark'] ?? null;
                    $o->postal_code   = $draft['postal_code'] ?? null;
                    $o->country       = $draft['country'] ?? null;
                    $o->state         = $draft['state'] ?? null;
                    $o->city          = $draft['city'] ?? null;
                }

                $o->name   = $draft['name'] ?? '';
                $o->email  = $draft['email'] ?? '';
                $o->mobile = $draft['mobile'] ?? '';

                if (!empty($draft['discount'])) {
                    $o->offer_code      = $draft['discount']['offer_code'] ?? '';
                    $o->discount_amount = \helper::number_format($draft['discount']['offer_amount'] ?? 0);
                } else {
                    $o->offer_code      = '';
                    $o->discount_amount = \helper::number_format(0);
                }

                // Barion tranzakció jelölése
                $o->transaction_type = 16; // Barion
                $o->transaction_id   = $paymentId;
                $o->payment_status   = 2;   // fizetve

                $o->tax_amount      = $draft['tax'] ?? 0;
                $o->tax_name        = $draft['tax_name'] ?? '';
                $o->delivery_charge = \helper::number_format($draft['delivery_charge'] ?? 0);
                $o->grand_total     = \helper::number_format($draft['grand_total'] ?? 0);

                $o->order_notes   = $draft['order_notes'] ?? '';
                $o->order_from    = 'web';
                $o->status        = $defaults->id;
                $o->status_type   = $defaults->type;
                $o->delivery_date = $draft['delivery_date'] ?? null;
                $o->delivery_time = $draft['delivery_time'] ?? null;

                $o->save();

                // tételek
                foreach (($draft['items'] ?? []) as $it) {
                    $od = new \App\Models\OrderDetails();
                    $od->order_id            = $o->id;
                    $od->user_id             = $checkuser?->id;
                    $od->item_id             = $it['item_id'] ?? null;
                    $od->item_name           = $it['item_name'] ?? '';
                    $od->item_type           = $it['item_type'] ?? null;
                    $od->item_image          = $it['item_image'] ?? '';
                    $od->tax                 = $it['tax'] ?? 0;
                    $od->qty                 = $it['qty'] ?? 1;
                    $od->item_price          = $it['item_price'] ?? 0;
                    $od->addons_id           = $it['addons_id'] ?? null;
                    $od->addons_name         = $it['addons_name'] ?? '';
                    $od->addons_price        = $it['addons_price'] ?? 0;
                    $od->addons_total_price  = $it['addons_total_price'] ?? 0;
                    $od->extras_id           = $it['extras_id'] ?? null;
                    $od->extras_name         = $it['extras_name'] ?? '';
                    $od->extras_price        = $it['extras_price'] ?? 0;
                    $od->extras_total_price  = $it['extras_total_price'] ?? 0;
                    $od->save();
                }

                // kosár ürítés
                $buyNow = (int)($draft['buynow'] ?? 0);
                if ($checkuser) {
                    \App\Models\Cart::where('user_id', $checkuser->id)->where('buynow', $buyNow)->delete();
                } elseif (!empty($draft['session_id'])) {
                    \App\Models\Cart::where('session_id', $draft['session_id'])->where('buynow', $buyNow)->delete();
                }

                // opcionális loyalty
                if ($checkuser) {
                    $grandTotalFt = (int) round($o->grand_total);
                    $bonus = (int) (floor($grandTotalFt / 1000) * 50);
                    if ($bonus > 0) {
                        $cacheKey = 'loyalty_bonus_applied_order_' . $o->id;
                        if (\Illuminate\Support\Facades\Cache::add($cacheKey, 1, now()->addDays(30))) {
                            \Illuminate\Support\Facades\DB::table('users')
                                ->where('id', $checkuser->id)
                                ->update(['wallet' => \Illuminate\Support\Facades\DB::raw('wallet + ' . (int)$bonus)]);
                        }
                    }
                }

                // success URL eltárolása (after redirect-hez)
                \Illuminate\Support\Facades\Cache::put(
                    'barion_success_url_'.$paymentId,
                    url('/success-'.$o->order_number),
                    now()->addMinutes(30)
                );
            });
        } catch (\Throwable $e) {
            \Log::error("Barion callback txn error for {$paymentId}: ".$e->getMessage());
            \App\Models\PaymentLog::create([
                'payment_id' => $paymentId,
                'event'      => 'CALLBACK_ERROR',
                'message'    => 'DB transaction exception: '.$e->getMessage()
            ]);
            // mindig 200
            return response('OK', 200);
        }

        return response('OK', 200);
    }

}
