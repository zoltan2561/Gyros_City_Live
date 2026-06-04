<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\helper;
use App\Models\Notification;
use App\Models\Category;
use App\Models\Item;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Mail;
class NotificationController extends Controller
{
    public function index()
    {
        $getnotification = Notification::with('category_info', 'item_info')->orderbyDesc('id')->get();
        $getsettings = Settings::first();
        return view('admin.notification.notification', compact('getnotification', 'getsettings'));
    }
    public function add()
    {
        $getitem = Item::where('item_status', '1')->orderBy('reorder_id')->get();
        $getcategory = Category::where('is_available', '1')->orderBy('reorder_id')->get();
        return view('admin.notification.add', compact('getitem', 'getcategory'));
    }
    public function store(Request $request)
    {
        $notification = new Notification;
        $notification->title = $request->title;
        $notification->message = $request->message;
        $notification->cat_id = $request->cat_id;
        $notification->item_id = $request->item_id;
        $notification->save();
        $getallusers = User::select('token')->where('type', '2')->where('is_available', '1')->where('is_notification', '1')->get();

        $type = "promotion";
        $sub_type = "";
        if ($request->has('cat_id') && $request->cat_id != "") {
            $catdata = Category::find($request->cat_id);
            $category_id = $request->cat_id;
            $category_name = $catdata->category_name;
            $sub_type = "category";
        } else {
            $category_id = "";
            $category_name = "";
        }
        if ($request->has('item_id') && $request->item_id != "") {
            $item_id = $request->item_id;
            $sub_type = "item";
        } else {
            $item_id = "";
        }
        $customdata = array(
            'type' => $type,
            'sub_type' => $sub_type,
            'category_id' => $category_id,
            'category_name' => $category_name,
            'item_id' => $item_id,
            'order_id' => "",
        );
        foreach ($getallusers as $checkuser) {
            $msg = array(
                'body' => $request->message,
                'title' => $request->title,
                'sound' => 1/*Default sound*/
            );
            $fields = array(
                'to' => $checkuser->token,
                'notification' => $msg,
                'data' => $customdata
            );
            $headers = array(
                'Authorization: key=' . helper::appdata()->firebase,
                'Content-Type: application/json'
            );
            #Send Reponse To FireBase Server
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
        }
        return redirect('admin/notification')->with('success', trans('messages.success'));
    }


    //Email értesítés
    public function unprocessedAlert(Request $request)
    {
        // címek configból (string vagy vesszővel elválasztott lista)
        $to = trim((string) config('MAIL_ADMIN_EMAIL', 'admin@gyroscity.eu,blausilverkft@gmail.com,fuleczkikinga23@gmail.com,markdemetet@gmail.com'));
        //$to = trim((string) config('MAIL_ADMIN_EMAIL', 'pappzoltan6969@gmail.com,admin@gyroscity.eu'));
        if ($to === '') {
            return response()->json(['ok' => false, 'msg' => 'No admin email configured'], 200);
        }
        $adminName = (string) config('MAIL_ADMIN_NAME', 'Admin');

        // több cím támogatása
        $recipients = array_filter(array_map('trim', explode(',', $to)));




        // szerver-oldali throttle: 10 percenként max 1 levél
        // (így több megnyitott admin ablak sem spammel)
        $cacheKey = 'unprocessed_order_alert_lock';

        if (!Cache::add($cacheKey, 1, now()->addMinutes(10))) {
            return response()->json(['ok' => true, 'throttled' => true], 200);
        }

        $count   = (int) $request->input('count', 0);
        $subject = 'Figyelmeztetés: FÜGGŐ online rendelés';
        $body    = "Függő rendelés található.\n"
            . "Lehetséges, hogy van nem feldolgozott online rendelés.\n"
            . "Időpont: " . now()->toDateTimeString() . "\n"
            . "Admin: " . url('/admin');

        // egyszerű raw levél (használja a beállított mail drivert)
        Mail::raw($body, function ($message) use ($recipients, $subject, $adminName) {
            foreach ($recipients as $addr) {
                $message->to($addr, $adminName);
            }
            $message->subject($subject);
        });

        return response()->json(['ok' => true], 200);
    }

}
