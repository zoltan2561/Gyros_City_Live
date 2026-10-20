<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\helper;
use App\Helpers\sms_helper;
use App\Helpers\whatsapp_helper;
use App\Models\CustomStatus;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\OrderDetails;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $getorders = Order::query()->where('order_from', '!=', 'pos');

        if ($request->has('status') && $request->status != "") {
            if ($request->status == "processing") {
                $getorders = $getorders->whereIn('status_type', array(1, 2));
            }
            if ($request->status == "completed") {
                $getorders = $getorders->where('status_type', 3);
            }
            if ($request->status == "cancelled") {
                $getorders = $getorders->where('status_type', 4);
            }
        }
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $getorders->where(function ($query) use ($search) {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%');
            });
        }
        $getorders = $getorders->orderByDesc('id')->paginate(10)->withQueryString();
        $totals = Order::where('order_from', '!=', 'pos')->selectRaw('COUNT(*) AS total,
            SUM(CASE WHEN status_type IN (1, 2) THEN 1 ELSE 0 END) AS processing,
            SUM(CASE WHEN status_type = 3 THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status_type = 4 THEN 1 ELSE 0 END) AS cancelled')->first();
        $totalorders = (int) $totals->total;
        $totalprocessing = (int) $totals->processing;
        $totalcompleted = (int) $totals->completed;
        $totalcancelled = (int) $totals->cancelled;
        $orderStatusNames = CustomStatus::all(['id', 'type', 'order_type', 'name'])
            ->mapWithKeys(fn ($status) => [$status->id.':'.$status->type.':'.$status->order_type => $status->name])->all();
        $orderPaymentNames = Payment::pluck('payment_name', 'payment_type')->all();
        $getdriver = User::where('type', '3')->where('is_available', 1)->orderByDesc('id')->get(['id', 'name']);
        $ordersPaginated = true;
        return view('admin.orders.index', compact('getorders', 'getdriver', 'totalorders', 'totalprocessing', 'totalcompleted', 'totalcancelled', 'orderStatusNames', 'orderPaymentNames', 'ordersPaginated'));
    }

    public function update(Request $request)
    {
        $orderdata = Order::find($request->id);
        $user_info = User::find($orderdata->user_id);

        $title = "";
        $message_text = "";
        $body = "";

        if ($request->statustype == "2") {
            $title = @helper::gettype($request->status, $request->statustype, $orderdata->order_type)->name;
            $body = 'A ' . $orderdata->order_number . ' számú rendelésének státusza.';
            $message_text = $body;
        }
        elseif ($request->statustype == "3") {
            $title = @helper::gettype($request->status, $request->statustype, $orderdata->order_type)->name;
            // Ha elvitel: "elkészült", ha házhozszállítás: "kézbesítve" – alább a kézbesített verzió:
            $body = 'A ' . $orderdata->order_number . ' számú rendelését sikeresen kézbesítettük.';
            $message_text = $body;
        }
        if ($request->statustype == "4") {
            $title = @helper::gettype($request->status, $request->statustype, $orderdata->order_type)->name;
            $body = 'Rendelés: ' . $orderdata->order_number . ' törölve az admin által.';
            $message_text = 'Rendelés :' . $orderdata->order_number . ' törölve az admin által.';
        }
        if ($request->statustype == "4") {
            // order cancelled by admin
            $title = trans('labels.order_cancelled');
            $body = 'Rendelés ' . $orderdata->order_number . 'Törölve az admin által.';
            $message_text = 'Rendelés' . $orderdata->order_number . 'törölve az admin által.';

            if ($orderdata->user_id != null) {
                if ($orderdata->transaction_type != 1) {
                    if ($orderdata->user_id != null) {
                        $user_info->wallet += $orderdata->grand_total;
                    }
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
        }

        if ($orderdata->user_id != null) {
            if ($user_info->is_notification == 1) {
                $noti = helper::push_notification($user_info->token, $title, $body, "order", $orderdata->id);
            }

            if ($user_info->is_mail == 1) {
                if (@helper::checkaddons('otp')) {

                    $status_sms = sms_helper::order_status_sms($user_info->mobile, $user_info->name, $title, $message_text);
                } else {
                    $status_email = helper::order_status_email($user_info->email, $user_info->name, $title, $message_text);
                }
            }
        }

        $defaultsatus = CustomStatus::where('order_type', $orderdata->order_type)->where('type', $request->statustype)->where('id', $request->status)->where('is_available', 1)->where('is_deleted', 2)->first();
        $orderdata->status = $defaultsatus->id;
        $orderdata->status_type = $defaultsatus->type;

        if (@helper::checkaddons('whatsapp_message')) {
            if (whatsapp_helper::whatsapp_message_config()->status_change == 1) {
                whatsapp_helper::orderupdatemessage($orderdata->order_number, $title);
            }
        }

        if ($orderdata->save()) {
            return 1;
        } else {
            return 0;
        }
    }

    public function assign_driver(Request $request)
    {
        $orderdata = Order::find($request->order_id);
        $user_info = User::find($orderdata->user_id);
        $driver_info = User::find($request->driver_id);
        // for user
        if ($orderdata->user_id != null) {
            $title = trans('messages.driver_assigned_title');
            $body = 'Delivery boy' . $driver_info->name . ' has been assigned to your Order ' . $orderdata->order_number;
            $message_text = 'Delivery boy ' . $driver_info->name . ' has been assigned to your Order ' . $orderdata->order_number;
            $noti = helper::push_notification($user_info->token, $title, $body, "order", $orderdata->id);

            if (@helper::checkaddons('otp')) {
                $status_sms = sms_helper::order_status_sms($user_info->email, $user_info->name, $title, $message_text);
            } else {
                $status_email = helper::order_status_email($user_info->email, $user_info->name, $title, $message_text);
            }
        }


        // for driver
        $title = trans('messages.new_order_assigned_title');
        $body = 'New Order ' . $orderdata->order_number . ' assigned to you';
        $message_text = 'New order ' . $orderdata->order_number . ' has been assigned to you.';
        $noti = helper::push_notification($driver_info->token, $title, $body, "order", $orderdata->id);


        $status_email = helper::order_status_email($driver_info->email, $driver_info->name, $title, $message_text);


        $orderdata->driver_id = $request->driver_id;
        $orderdata->save();

        if (@helper::checkaddons('whatsapp_message')) {
            if (whatsapp_helper::whatsapp_message_config()->status_change == 1) {
                whatsapp_helper::orderupdatemessage($orderdata->order_number, trans('messages.driver_assigned_title'));
            }
        }
        return response()->json(['status' => 1, "message" => trans('messages.success')], 200);
    }
    public function invoice(Request $request)
    {
        $od = Order::where('id', $request->id)->first();
        $orderdata = Order::with('user_info', 'driver_info')->where('order.id', $request->id)->first();
        $ordersdetails = OrderDetails::where('order_details.order_id', $request->id)->get();
        $getdriver = User::where('type', '3')->where('is_available', 1)->orderByDesc('id')->get();
        return view('admin.orders.invoice', compact('orderdata', 'ordersdetails', 'getdriver'));
    }
    public function print(Request $request)
    {
        $orderdata = Order::with('user_info', 'driver_info')->where('order.id', $request->id)->first();
        $ordersdetails = OrderDetails::where('order_details.order_id', $request->id)->get();
        return view('admin.orders.print', compact('orderdata', 'ordersdetails'));
    }
    public function generatepdf(Request $request)
    {
        $getorderdata = Order::with('user_info', 'driver_info')->where('order.id', $request->id)->first();
        $ordersdetails =  OrderDetails::where('order_details.order_id', $request->id)->get();
        $pdf = Pdf::loadView('admin.orders.invoicepdf', ['getorderdata' => $getorderdata, 'ordersdetails' => $ordersdetails]);
        return $pdf->download('orderinvoice.pdf');
    }
    public function order_note(Request $request)
    {
        $updatenote = Order::where('order_number', $request->order_id)->first();
        $updatenote->admin_notes = $request->admin_notes;
        $updatenote->update();
        return redirect()->back()->with('success', trans('messages.success'));
    }
    public function customerbillinfo(Request $request)
    {
        $customerinfo = Order::where('order_number', $request->order_id)->first();
        if ($request->edit_type == "customer_info") {
            $customerinfo->name = $request->user_name;
            $customerinfo->mobile = $request->user_mobile;
            $customerinfo->email = $request->user_email;
        }
        if ($request->edit_type == "bill_info") {
            $customerinfo->address = $request->bill_address;
            $customerinfo->city = $request->bill_city;
            $customerinfo->state = $request->bill_state;
            $customerinfo->country = $request->bill_country;
            $customerinfo->landmark = $request->bill_landmark;
            $customerinfo->postal_code = $request->bill_pincode;
        }
        $customerinfo->update();
        return redirect()->back()->with('success', trans('messages.success'));
    }

    public function get_reports(Request $request)
    {
        $getorders = array();
        $totalprocessing = 0;
        $totalcompleted = 0;
        $totalcancelled = 0;
        $totalearnings = 0;
        $totalrevenue = 0;
        if (!empty($request->startdate) && !empty($request->enddate)) {
            $getorders = Order::with('user_info', 'driver_info')->select('order.*')
                ->whereBetween('order.created_at', [$request->startdate, $request->enddate])
                ->orderByDesc('id')
                ->get();
            $totalprocessing = Order::whereNotIn('status', array(5, 6, 7))->whereBetween('created_at', [$request->startdate, $request->enddate])->count();
            $totalcompleted = Order::where('status', 5)->whereBetween('created_at', [$request->startdate, $request->enddate])->count();
            $totalcancelled = Order::whereIn('status', array(6, 7))->whereBetween('created_at', [$request->startdate, $request->enddate])->count();
            $totalearnings = Order::where('status', 5)->whereBetween('created_at', [$request->startdate, $request->enddate])->sum('grand_total');
        }
        $getdriver = User::where('is_available', '1')->where('type', '3')->get();
        return view('admin.orders.report', compact('getorders', 'getdriver', 'totalprocessing', 'totalcompleted', 'totalcancelled', 'totalearnings'));
    }

    public function payment_status(Request $request)
    {
        date_default_timezone_set(@helper::appdata()->timezone);
        if ($request->ramin_amount > 0) {
            return redirect()->back()->with('error', trans('messages.amount_validation_msg'));
        }
        $order = Order::where('order_number', $request->booking_number)->first();
        $order->payment_status = 2;
        $order->update();
        return redirect()->back()->with('success', trans('messages.success'));
    }
}
