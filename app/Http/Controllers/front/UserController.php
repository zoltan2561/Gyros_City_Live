<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Helpers\helper;
use App\Helpers\sms_helper;
use App\Models\User;
use App\Models\OTPConfiguration;
use App\Models\SystemAddons;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;
use Illuminate\Support\Facades\Schema;   // + ezt add hozzá

class UserController extends Controller
{
    public function register()
    {
        if (@helper::checkaddons('customer_login')) {
            if (helper::appdata()->login_required == 1) {
                return view('web.auth.register');
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }
    public function create(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required',
            'email'   => 'required|email',
            'mobile'  => 'required|numeric',
            'checkbox'=> 'accepted',
        ], [
            'name.required'       => trans('messages.name_required'),
            'email.required'      => trans('messages.email_required'),
            'email.email'         => trans('messages.valid_email'),
            'mobile.required'     => trans('messages.mobile_required'),
            'mobile.numeric'      => trans('messages.numbers_only'),
            'checkbox.accepted'   => trans('messages.accept_terms'),
        ]);

        $email = "";
        $password = "";
        $login_type = "";
        $google_id = "";
        $facebook_id = "";

        if (session()->has('social_login')) {
            if (session()->get('social_login')['google_id'] != "") {
                $login_type = "google";
                $google_id  = session()->get('social_login')['google_id'];
                $email      = session()->get('social_login')['email'];
            }
            if (session()->get('social_login')['facebook_id'] != "") {
                $login_type  = "facebook";
                $facebook_id = session()->get('social_login')['facebook_id'];
                $email       = session()->get('social_login')['email'];
            }
        } else {
            $email      = $request->email;
            $password   = \Hash::make($request->password);
            $login_type = "email";
        }

        // Referral és egyediség ellenőrzés
        $checkreferral = User::select('id','name','referral_code','wallet','email','token')
            ->where('referral_code', $request->referral_code)
            ->where('is_available', 1)->where('is_deleted', 2)->first();

        if ($request->filled('referral_code') && empty($checkreferral)) {
            return redirect()->back()->with('error', trans('messages.invalid_referral_code'));
        }

        if (User::where('mobile',$request->mobile)->where('is_available',1)->where('is_deleted',2)->exists()) {
            return redirect()->back()->with('error', trans('messages.mobile_exist'));
        }
        if (User::where('email',$request->email)->where('is_available',1)->where('is_deleted',2)->exists()) {
            return redirect()->back()->with('error', trans('messages.email_exist'));
        }

        // 1) User létrehozása PENDING állapotban (is_verified = 2)
        $user = new User;
        $user->name          = $request->name;
        $user->mobile        = $request->mobile;
        $user->email         = $email;
        $user->profile_image = 'unknown.png';
        $user->password      = $password;     // social login esetén maradhat üres
        $user->login_type    = $login_type;
        $user->google_id     = $google_id;
        $user->facebook_id   = $facebook_id;
        $user->referral_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz'), 0, 10);
        $user->otp           = null;
        $user->type          = 2;
        $user->is_available  = 1;
        $user->is_verified   = 2;             // <-- fontos: függőben

        if ($request->filled('referral_code') && !empty($checkreferral)) {
            $user->user_id         = $checkreferral->id;
            $user->referral_amount = helper::appdata()->referral_amount;
        }

        // NEM állítunk email_verified_at-et itt
        $user->save();
        session()->forget('social_login');

        // 2) OTP generálás + küldés (SMS addon esetén SMS, különben e-mail)
        $otp = rand(100000, 999999);

// mail konfig frissítése (nálatok így szokás)
        $emaildata = helper::emailconfigration();
        \Config::set('mail', $emaildata);

// KÜLDÉS
        $sent = helper::verificationemail($user->email, $otp);
        if ($sent != 1) {
            return redirect()->back()->with('error', trans('messages.email_error'));
        }

// OTP mentése a userhez + session beállítás a verify oldalnak
        $user->otp = $otp;
        $user->is_verified = 2; // függőben
        $user->save();

        session()->put('verification_email', $user->email);
        if (env('Environment') == 'sendbox') {
            session()->put('verification_otp', $otp);
        }

// irány az OTP ellenőrző oldal
        return redirect()->route('verification')->with('success', trans('messages.email_sent'));

    }


    public function verification(Request $request)
    {
        return view('web.auth.verification');
    }
    public function verifyotp(Request $request)
    {
        if (@helper::checkaddons('otp')) {
            $mobile = session()->get('verification_email');
            $checkuser = User::where('mobile', $mobile)->where('type', 2)->first();
        } else {
            $email = session()->get('verification_email');
            $checkuser = User::where('email', $email)->where('is_verified', 2)->first();
        }

        if (!empty($checkuser)) {
            $is_valid_otp = 2;
            if (@helper::checkaddons('otp')) {

                $getconfiguration = OTPConfiguration::where('status', 1)->first();
                if ($getconfiguration->name == "msg91") {
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => "https://api.msg91.com/api/v5/otp/verify?authkey=" . $getconfiguration->msg_authkey . "&mobile=" . $mobile . "&otp=" . $request->otp . "",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                    ));
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                    $response = json_decode($response);
                    $is_valid_otp = $response->type == "error" ? 2 : 1;
                } else {
                    $is_valid_otp = $checkuser->otp != $request->otp ? 2 : 1;
                }
            }


            if ($checkuser->otp == $request->otp || $is_valid_otp == 1) {
                $checkuser->otp = null;
                $checkuser->is_verified = 1;
                $checkuser->save();
                session()->forget('verification_email');
                session()->forget('social_login');
                session()->forget('verification_otp');

                // CHECK_USER_HAS_REFFERAL_USER
                if ($checkuser->user_id > 0) {
                    // ---- for referral user ------
                    $checkreferral = User::find($checkuser->user_id);
                    $checkreferral->wallet += $checkuser->referral_amount;
                    $checkreferral->referral_amount = $checkuser->referral_amount;
                    $checkreferral->save();
                    $referral_tr = new Transaction;
                    $referral_tr->user_id = $checkreferral->id;
                    $referral_tr->amount = $checkuser->referral_amount;
                    $referral_tr->transaction_type = 101;
                    $referral_tr->username = $checkuser->name;
                    $referral_tr->save();
                    // ---- for new user ------
                    $new_user_tr = new Transaction;
                    $new_user_tr->user_id = $checkuser->id;
                    $new_user_tr->amount = $checkuser->referral_amount;
                    $new_user_tr->transaction_type = 101;
                    $new_user_tr->username = $checkreferral->name;
                    $new_user_tr->save();
                    $checkuser->wallet = $checkuser->referral_amount;
                    $checkuser->user_id = "";
                    $checkuser->referral_amount = 0;
                    $checkuser->save();
                    $title = trans('labels.referral_earning');
                    $body = 'Your friend "' . $checkuser->name . '" has used your referral code to register with Our Restaurant. You have earned "' . helper::currency_format(helper::appdata()->referral_amount) . '" referral amount in your wallet.';
                    helper::push_notification($checkreferral->token, $title, $body, "wallet", "");
                    $referralmessage = 'Your friend "' . $checkuser->name . '" has used your referral code to register with Restaurant User. You have earned "' . helper::appdata()->currency . '' . number_format(helper::appdata()->referral_amount, 2) . '" referral amount in your wallet.';
                    $emaildata = helper::emailconfigration();
                    Config::set('mail', $emaildata);
                    helper::referral($checkreferral->email, $checkuser->name, $checkreferral->name, $referralmessage);
                }

                if (@helper::checkaddons('otp')) {
                    Auth::loginUsingId($checkuser->id, true);
                    return redirect('/')->with('success', trans('messages.success'));
                } else {
                    return redirect(route('login'))->with('success', trans('messages.success'));
                }
            } else {
                return redirect()->back()->with('error', trans('messages.invalid_otp'));
            }
        } else {
            return redirect()->back()->with('error', trans('messages.invalid_user'));
        }
    }
    public function resendotp()
    {
        $otp = rand(100000, 999999);

        if (@helper::checkaddons('otp')) {
            $mobile = session()->get('verification_email');
            $checkuser = User::where('mobile', $mobile)->where('is_deleted', 2)->first();
            $verification = sms_helper::verificationsms($mobile, $otp);
        } else {
            $email = session()->get('verification_email');
            $checkuser = User::where('email', $email)->where('is_deleted', 2)->first();
            $emaildata = helper::emailconfigration();
            Config::set('mail', $emaildata);
            $verification = helper::verificationemail($email, $otp);
        }
        if ($verification == 1) {
            $checkuser->otp = $otp;
            $checkuser->is_verified = 2;
            $checkuser->save();
            if (env('Environment') == 'sendbox') {
                session()->put('verification_otp', $otp);
            }
            return redirect()->back()->with('success', trans('messages.email_sent'));
        } else {
            return redirect()->back()->with('error', trans('messages.email_error'));
        }
    }
    public function login(Request $request)
    {
        if (@helper::checkaddons('customer_login')) {
            if (helper::appdata()->login_required == 1) {
                return view('web.auth.login');
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function checklogin(Request $request)
    {
        if (@helper::checkaddons('otp')) {
            $checkuser = User::where('mobile', $request->mobile)->where('is_deleted', 2)->where('type', 2)->first();
            if (!empty($checkuser)) {
                if ($checkuser->is_available == 1) {
                    $otp = rand(100000, 999999);
                    $send_otp = sms_helper::verificationsms($checkuser->mobile, $otp);
                    if ($send_otp == 1) {
                        $checkuser->otp = $otp;
                        $checkuser->save();
                        session()->put('verification_email', $request->mobile);
                        if (env('Environment') == 'sendbox') {
                            session()->put('verification_otp', $otp);
                        }
                        return redirect(route('verification'))->with('success', trans('messages.success'));
                    } else {
                        return redirect()->back()->with('error', trans('messages.wrong'));
                    }
                } else {
                    return redirect(route('login'))->with('error', trans('messages.blocked'));
                }
            } else {
                return redirect(route('login'))->with('error', trans('messages.invalid_user'));
            }
        } else {
            if (Auth::attempt($request->only('email', 'password'))) {
                if (Auth::user()->type == 2) {
                    if (Auth::user()->is_available == 1) {
                        if (Auth::user()->is_verified == 1) {

                            return redirect(route('home'));
                        } else {
                            $otp = rand(100000, 999999);
                            $verification = helper::verificationemail($request->email, $otp);
                            if ($verification == 1) {
                                $checkuser = User::find(Auth::user()->id);
                                $checkuser->otp = $otp;
                                $checkuser->save();
                                if (env('Environment') == 'sendbox') {
                                    session()->put('verification_otp', $otp);
                                }
                                Auth::logout();
                                return redirect(route('verification'))->with('success', trans('messages.email_sent'));
                            } else {
                                Auth::logout();
                                return redirect()->back()->with('error', trans('messages.email_error'));
                            }
                        }
                    } else {
                        Auth::logout();
                        return redirect()->back()->with('error', trans('messages.blocked'));
                    }
                } else {
                    Auth::logout();
                    return redirect(route('login'))->with('error', trans('messages.email_pass_invalid'));
                }
            } else {
                Auth::logout();
                return redirect(route('login'))->with('error', trans('messages.email_pass_invalid'));
            }
        }
    }

    public function forgotpassword(Request $request)
    {
        if (@helper::checkaddons('customer_login')) {
            if (helper::appdata()->login_required == 1) {
                return view('web.auth.forgot_password');
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    public function sendpass(Request $request)
    {
        $checkuser = User::where('email', $request->email)->where('type', 2)->where('is_deleted', 2)->where('is_available', 1)->first();
        if (!empty($checkuser)) {
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $emaildata = helper::emailconfigration();
            Config::set('mail', $emaildata);
            $pass = helper::send_pass($checkuser->email, $checkuser->name, $password);
            if ($pass == 1) {
                $checkuser->password = Hash::make($password);
                $checkuser->save();
                return redirect(route('login'))->with('success', trans('messages.password_sent'));
            } else {
                return redirect()->back()->with('error', trans('messages.email_error'));
            }
        } else {
            return redirect()->back()->with('error', trans('messages.invalid_email'));
        }
    }

    public function getprofile(Request $request)
    {
        return view('web.profile.profile');
    }
    public function editprofile(Request $request)
    {
        if ($request->hasFile('profile_image')) {
            $validator = Validator::make($request->all(), [
                'profile_image' => 'image',
            ], [
                "profile_image.image" => trans('messages.enter_image_file'),
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                if (Auth::user()->profile_image != "unknown.png" && file_exists(env('ASSETSPATHURL') . 'admin-assets/images/profile/' . Auth::user()->profile_image)) {
                    unlink(env('ASSETSPATHURL') . 'admin-assets/images/profile/' . Auth::user()->profile_image);
                }
                $file = $request->file("profile_image");
                $filename = 'profile-' . time() . "." . $file->getClientOriginalExtension();
                $file->move(env('ASSETSPATHURL') . 'admin-assets/images/profile', $filename);
                $checkuser = User::find(Auth::user()->id);
                $checkuser->profile_image = $filename;
                $checkuser->save();
            }
        }
        $checkuser = User::find(Auth::user()->id);
        $checkuser->name = $request->name;
        $checkuser->save();
        return redirect()->back()->with('success', trans('messages.success'));
    }
    public function send_email_status(Request $request)
    {
        if (Auth::user() && Auth::user()->type == 2) {
            $checkuser = User::find(Auth::user()->id);
            $checkuser->is_mail = $checkuser->is_mail == 1 ? 2 : 1;
            $checkuser->save();
            return redirect(url()->previous())->with('success', trans('messages.success'));
        }
        return redirect('/');
    }
    public function referearn(Request $request)
    {
        return view('web.referearn.referearn');
    }
    public function changepassword(Request $request)
    {
        return view('web.changepassword');
    }
    public function updatepassword(Request $request)
    {
        $request->validate([
            'confirm_password' => 'same:new_password'
        ], [
            'confirm_password.same' => trans('messages.confirm_password_same')
        ]);
        if (Hash::check($request->old_password, Auth::user()->password)) {
            if ($request->old_password == $request->new_password) {
                return redirect()->back()->with('error', trans('messages.new_password_diffrent'));
            } else {
                $pass = User::find(Auth::user()->id);
                $pass->password = Hash::make($request->new_password);
                $pass->save();
                return redirect()->back()->with("success", trans('messages.success'));
            }
        } else {
            return redirect()->back()->with("error", trans('messages.old_password_invalid'));
        }
    }
    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect(route('home'));
    }
}
