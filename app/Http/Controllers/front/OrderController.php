<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Helpers\helper;
use App\Helpers\whatsapp_helper;
use App\Models\CustomStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Transaction;
use App\Models\OrderDetails;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $getorders = Order::select('order.id', 'order.order_from', 'order.order_type', 'order.order_number', 'order.grand_total', 'order.status', 'order.status_type', 'order.transaction_type', 'order.created_at')
            ->where('order.user_id', Auth::user()->id);
        if ($request->has('type') && $request->type == "completed") {
            $getorders = $getorders->where('status_type', 3);
        } else if ($request->has('type') && $request->type == "cancelled") {
            $getorders = $getorders->where('status_type', 4);
        } else {
            // processing
            $getorders = $getorders->whereIn('status_type', array(1, 2));
        }
        $getorders = $getorders->where('order.order_from', '!=', 'pos')->orderByDesc('id')->paginate(10);
        $totalprocessing = Order::whereIn('status_type', array(1, 2))->where('order_from', '!=', 'pos')->where('user_id', Auth::user()->id)->count();
        $totalcompleted = Order::where('status_type', 3)->where('order_from', '!=', 'pos')->where('user_id', Auth::user()->id)->count();
        $totalcancelled = Order::where('status_type', 4)->where('order_from', '!=', 'pos')->where('user_id', Auth::user()->id)->count();
        return view('web.orders.orders', compact('getorders', 'totalprocessing', 'totalcompleted', 'totalcancelled'));
    }
    public function statusupdate(Request $request)
    {
        $orderdata = Order::where('order_number', $request->id)->first();
        if (Auth::user()) {
            $user_info = User::find(Auth::user()->id);
        }
        if (!empty($orderdata) && $orderdata->status == 1) {

            if (Auth::user()) {
                if ($orderdata->transaction_type != 1) {
                    $user_info->wallet += $orderdata->grand_total;
                    $transaction = new Transaction;
                    $transaction->user_id = $orderdata->user_id;
                    $transaction->order_id = $orderdata->id;
                    $transaction->order_number = $orderdata->order_number;
                    $transaction->amount = $orderdata->grand_total;
                    $transaction->transaction_id = $orderdata->transaction_id;
                    $transaction->transaction_type = '2';
                    if ($transaction->save()) {
                        $user_info->save();
                    }
                }
            }
            $title = trans('labels.order_cancelled');

            if (@helper::checkaddons('whatsapp_message')) {
                if (whatsapp_helper::whatsapp_message_config()->status_change == 1) {
                    whatsapp_helper::orderupdatemessage($orderdata->order_number, $title);
                }
            }

            // order cancelled by User
            $defaultsatus = CustomStatus::where('order_type', $orderdata->order_type)->where('type', 4)->where('is_available', 1)->where('is_deleted', 2)->first();

            if (empty($defaultsatus) && $defaultsatus == null) {
                return 0;
            }
            $orderdata->status = $defaultsatus->id;
            $orderdata->status_type = $defaultsatus->type;
            if ($orderdata->save()) {
                app(\App\Services\OrderNotifications::class)->statusChanged($orderdata);
                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
    public function orderdetails(Request $request)
    {
        $orderdata = Order::with('driver_info')->where('order_number', $request->order_number)->first();

        if (!empty($orderdata)) {
            $ordersdetails = OrderDetails::where('order_id', $orderdata->id)->orderByDesc('id')->get();
            $whmessage = "";
            if (@helper::checkaddons('whatsapp_message')) {
                if (whatsapp_helper::whatsapp_message_config()->order_created == 1) {
                    if (whatsapp_helper::whatsapp_message_config()->message_type == 2) {
                        $whmessage = whatsapp_helper::whatsappmessage($request->order_number);
                    }
                }
            }
            return view('web.orders.orderdetails', compact('orderdata', 'ordersdetails', 'whmessage'));
        } else {
            return redirect()->back()->with('error', trans('messages.wrong'));
        }
    }

    public function success(Request $request)
    {
        $orderdata = Order::select('order_number')->where('order_number', $request->order_number)->first();

        if (!empty($orderdata)) {
            $whmessage = "";
            if (@helper::checkaddons('whatsapp_message')) {
                if (whatsapp_helper::whatsapp_message_config()->order_created == 1) {
                    if (whatsapp_helper::whatsapp_message_config()->message_type == 2) {
                        $whmessage = whatsapp_helper::whatsappmessage($request->order_number);
                    } else {
                        whatsapp_helper::whatsappmessage($request->order_number);
                    }
                }
            }
            return view('web.orders.success', compact('orderdata', 'whmessage'));
        } else {
            return redirect()->back()->with('error', trans('messages.wrong'));
        }
    }
}
