<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\helper;
use App\Models\User;
use App\Models\Category;
use App\Models\Item;
use App\Models\Addons;
use App\Models\Ratting;
use App\Models\OrderDetails;
use App\Models\Banner;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Settings;
use App\Models\Time;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use DateTime;
use Session;

class AdminController extends Controller
{
    public function home(Request $request)
    {
        $gettotalcategory = Category::where('is_available', '1')->where('is_deleted', '2')->count();
        $getitems = Item::where('item_status', '1')->get();
        $addons = Addons::where('is_available', '1')->where('is_deleted', '2')->get();
        $getpromocode = Promocode::where('is_available', 1)->get();
        $getusers = User::Where('type', '=', '2')->get();
        $getdriver = User::where('is_available', '1')->where('type', '3')->get();
        $getreview = Ratting::all();
        $getorderscount = Order::all();
        $getorderdetailscount = OrderDetails::all();
        $banners = Banner::all();
        $order_total = Order::where('status', '!=', '6')->where('status', '!=', '7')->sum('grand_total');
        $order_tax = Order::where('status', '!=', '6')->where('status', '!=', '7')->sum('tax_amount');
        $getorders = Order::with('user_info')->select('order.*')->whereDate('created_at', Carbon::today())->get();

        $topitems = Item::with('category_info', 'subcategory_info', 'item_image')->leftJoin('order_details', 'order_details.item_id', 'item.id')
            ->select('item.id', 'item.cat_id', 'item.subcat_id', 'item.item_name', 'item.slug', DB::raw('count(order_details.item_id) as item_order_counter'))
            ->groupBy('order_details.item_id')
            ->orderByDesc('item_order_counter')
            ->having('item_order_counter', '>', 0)
            ->where('item.item_status', '1')
            ->get()->take(7);
        $topusers = User::leftJoin('order', 'order.user_id', 'users.id')
            ->select('users.id', 'users.name', 'users.email', 'users.mobile', 'profile_image', DB::raw('count(order.user_id) as user_order_counter'))
            ->groupBy('order.user_id')
            ->orderByDesc('user_order_counter')
            ->having('user_order_counter', '>', 0)
            ->where('users.type', '2')
            ->where('users.is_available', '1')
            ->get()->take(5);

        // ORDER-CHART-START
        $year = $request->getyear != "" ? $request->getyear : date('Y');
        $order_years = Order::select(DB::raw("YEAR(created_at) as year"))->groupBy(DB::raw("YEAR(created_at)"))->orderByDesc('created_at')->get();
        $orderlabels = Order::select(DB::raw("MONTHNAME(created_at) as month_name"))->whereYear('created_at', $year)->orderBy('created_at')->groupBy(DB::raw("MONTHNAME(created_at)"))->pluck('month_name');
        $deliverydata = $pickupdata = array();
        foreach ($orderlabels as $monthname) {
            $deliverydata[] = Order::whereYear('created_at', $year)->where('order_type', 1)->orderBy('created_at')->where(DB::raw("MONTHNAME(created_at)"), $monthname)->count();
            $pickupdata[] = Order::whereYear('created_at', $year)->where('order_type', 2)->orderBy('created_at')->where(DB::raw("MONTHNAME(created_at)"), $monthname)->count();
        }
        // ORDER-CHART-END


        // USERS-CHART-START
        $useryear = $request->useryear != "" ? $request->useryear : date('Y');
        $user_years = User::select(DB::raw("YEAR(created_at) as year"))->groupBy(DB::raw("YEAR(created_at)"))->orderByDesc('created_at')->get();
        $userslist = User::select(DB::raw("YEAR(created_at) as year"), DB::raw("MONTHNAME(created_at) as month_name"), DB::raw("COUNT(id) as total_user"))
            ->whereYear('created_at', $useryear)
            ->where('type', 2)
            ->orderBy('created_at')
            ->groupBy(DB::raw("MONTHNAME(created_at)"))
            ->pluck('total_user', 'month_name');
        $userslabels = $userslist->keys();
        $userdata = $userslist->values();
        // USERS-CHART-END

        // EARNINGS-CHART-START
        $earningsyear = $request->earningsyear != "" ? $request->earningsyear : date('Y');
        $earnings_years = Order::select(DB::raw("YEAR(created_at) as year"))->groupBy(DB::raw("YEAR(created_at)"))->orderByDesc('created_at')->get();
        $reviewslist = Order::select(DB::raw("YEAR(created_at) as year"), DB::raw("MONTHNAME(created_at) as month_name"), DB::raw("SUM(grand_total) as grand_total"))
            ->whereYear('created_at', $earningsyear)
            ->whereNotIn('status', array(1, 6, 7))
            ->orderBy('created_at')
            ->groupBy(DB::raw("MONTHNAME(created_at)"))
            ->pluck('grand_total', 'month_name');
        $earningslabels = $reviewslist->keys();
        $earningsdata = $reviewslist->values();
        // EARNINGS-CHART-END

        if (env('Environment') == 'sendbox') {
            $userslabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July ', 'August', 'September', 'October', 'November', 'December'];
            $userdata = [636, 1269, 2810, 2843, 3637, 467, 902, 1296, 402, 1173, 1509, 413];
            $earningslabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July ', 'August', 'September', 'October', 'November', 'December'];
            $earningsdata = [636, 1269, 2810, 2843, 2545, 467, 902, 1296, 402, 1173, 1509, 2000];
            $orderlabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July ', 'August', 'September', 'October', 'November', 'December'];
            $deliverydata = [285, 830, 550, 881, 130, 194, 213, 525, 245, 348, 581, 459];
            $pickupdata = [105, 343, 394, 299, 636, 984, 492, 135, 287, 250, 509, 121];
        }

        if ($request->ajax()) {
            return response()->json(['orderlabels' => $orderlabels, 'deliverydata' => $deliverydata, 'pickupdata' => $pickupdata, 'userslabels' => $userslabels, 'userdata' => $userdata, 'earningslabels' => $earningslabels, 'earningsdata' => $earningsdata], 200);
        } else {
            return view('admin.dashboard.home', compact('topitems', 'topusers', 'gettotalcategory', 'getitems', 'addons', 'getusers', 'banners', 'getreview', 'getorderscount', 'getorderdetailscount', 'order_total', 'order_tax', 'getpromocode', 'getorders', 'getdriver', 'order_years', 'orderlabels', 'deliverydata', 'pickupdata', 'user_years', 'userslabels', 'userdata', 'earnings_years', 'earningslabels', 'earningsdata'));
        }
    }
    public function getorder()
    {
        $orders = Order::where('status_type', 1)->where('order_from', '!=', 'pos')->orderBy('id')->pluck('id');
        $noti = Settings::value('notification_tune');
        return response()->json(['count' => $orders->count(), 'orders' => $orders, 'noti' => $noti]);
    }
    public function login()
    {
        return view('login');
    }
    public function profile()
    {
        return view('admin.auth.profile');
    }

    public function check_admin(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (Auth::attempt($request->only('email', 'password'), true)) {
            $user = Auth::user();
            if (AdminPermissions::isActive($user)) {
                $request->session()->regenerate();
                $request->session()->forget('admin_login');
                return redirect(AdminPermissions::landing($user));
            }
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return redirect('admin')->with('error', trans('messages.email_pass_invalid'));
    }

    public function send_pass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ],  [
            'email.required' => trans('messages.email_required'),
            'email.email' => trans('messages.valid_email'),
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            $checkadmin = User::where('email', $request->email)->whereIn('type', [1, 4])->first();
            if (!empty($checkadmin)) {
                $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                $pass = helper::send_pass($checkadmin->email, $checkadmin->name, $password);
                if ($pass == 1) {
                    $checkadmin->password = Hash::make($password);
                    $checkadmin->save();
                    return redirect('admin')->with('success', trans('messages.password_sent'));
                } else {
                    return redirect()->back()->with('error', trans('messages.email_error'));
                }
            } else {
                return redirect()->back()->with('error', trans('messages.invalid_email'));
            }
        }
    }
    public function changestatus(Request $request)
    {
        if (@helper::appdata()->timezone != "") {
            date_default_timezone_set(helper::appdata()->timezone);
        }

        $actor = auth()->user();
        if (!in_array((int)$actor->type, [1, 4])) {
            abort(403, 'Nincs jogosultság.');
        }

        // Jövőből várunk explicit státuszt (1=ON, 2=OFF); ha nincs, toggle
        $current = (int) \App\Models\User::where('type', 1)->value('is_online') ?: 1;
        $req     = $request->input('status'); // lehet GET vagy POST
        $new     = in_array((int)$req, [1, 2]) ? (int)$req : ($current === 1 ? 2 : 1);

        // SZINKRON: főadmin + ÖSSZES pultos
        \App\Models\User::whereIn('type', [1, 4])->update(['is_online' => $new]);


        // \Cache::forget('restaurant_open');

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'is_online' => $new]);
        }
        return back()->with('success', trans('messages.success'));
    }



    public function editprofile(request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:users,name,' . Auth::user()->id . ',id,is_deleted,2,type,1',
            'email' => 'required|unique:users,email,' . Auth::user()->id . ',id,is_deleted,2,type,1',
            'mobile' => 'required|unique:users,mobile,' . Auth::user()->id . ',id,is_deleted,2,type,1',
        ], [
            "name.required" => trans('messages.name_required'),
            "email.required" => trans('messages.email_required'),
            "email.unique" => trans('messages.email_exist'),
            "mobile.required" => trans('messages.mobile_required'),
            "mobile.unique" => trans('messages.mobile_exist'),
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            if ($request->hasfile('profile')) {
                if (Auth::user()->profile_image != "unknown.png" && file_exists(env('ASSETSPATHURL') . 'admin-assets/images/profile/' . Auth::user()->profile_image)) {
                    unlink(env('ASSETSPATHURL') . 'admin-assets/images/profile/' . Auth::user()->profile_image);
                }
                $profile = 'profile-' . uniqid() . '.' . $request->profile->getClientOriginalExtension();
                $request->profile->move(env('ASSETSPATHURL') . 'admin-assets/images/profile', $profile);
                $checkuser = User::find(Auth::user()->id);
                $checkuser->profile_image = $profile;
                $checkuser->save();
            }
            $checkuser = User::find(Auth::user()->id);
            $checkuser->name = $request->name;
            $checkuser->email = $request->email;
            $checkuser->mobile = $request->mobile;
            $checkuser->save();
            return redirect()->back()->with('success', trans('messages.success'));
        }
    }
    public function changepassword(request $request)
    {
        $validator = Validator::make($request->all(), [
            'newpassword' => 'required|min:6',
            'confirmpassword' => 'required|same:newpassword|min:6',
        ], [
            'newpassword.required' => trans('messages.new_password_required'),
            'confirmpassword.required_with' => trans('messages.confirm_password_required'),
            'confirmpassword.same' => trans('messages.confirm_password_same')
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        } else {
            if ($request->oldpassword == $request->newpassword) {
                return redirect()->back()->with('error', trans('messages.new_password_diffrent'));
            } else {
                if (Hash::check($request->oldpassword, Auth::user()->password)) {
                    $setting = User::where('id', Auth::user()->id)->update(['password' => Hash::make($request->newpassword)]);
                    return redirect()->back()->with('success', trans('messages.success'));
                } else {
                    return redirect()->back()->with('error', trans('messages.old_password_invalid'));
                }
            }
        }
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('admin/');
    }

    public function auth(Request $request)
    {
        User::where('id', 1)->update(['license_type' => 'extended']);
        return Redirect::to('/admin')->with('success', 'Success');
    }

    public function sessionsave(Request $request)
    {
        session()->put('demo', $request->demo_type);

        return response()->json(['status' => 1,'msg' => trans('messages.success')], 200);
    }
}
