<?php

namespace App\Http\Controllers\front;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Team;
use App\Models\Faq;
use App\Models\PrivacyPolicy;
use App\Models\RefundPolicy;
use App\Models\TermsCondition;
use App\Models\Aboutus;
use App\Models\Contact;
use App\Models\Time;
use App\Models\Ratting;
use App\Models\Subscribe;
use Illuminate\Http\Request;

class OtherPagesController extends Controller
{
    public function faq(Request $request)
    {
        $getfaqs = Faq::select("id", "title", "description")->orderBydesc('id')->get();
        return view('web.faq', compact('getfaqs'));
    }
    public function privacypolicy(Request $request)
    {
        $getprivacypolicy = PrivacyPolicy::first();
        return view('web.privacypolicy', compact('getprivacypolicy'));
    }
    public function termsconditions(Request $request)
    {
        $gettermscondition = TermsCondition::first();
        return view('web.termsconditions', compact('gettermscondition'));
    }
    public function gallery(Request $request)
    {
        $getgalleries = Gallery::select(\DB::raw("CONCAT('" . url(env('ASSETSPATHURL') . 'admin-assets/images/about') . "/', image) AS image_url"))->orderByDesc('id')->get();
        return view('web.gallery', compact('getgalleries'));
    }
    public function ourteam(Request $request)
    {
        $getteams = Team::select("id", "name", "designation", "fb", "youtube", "insta", "twitter", "description", "image")->orderBy('reorder_id')->get();
        return view('web.ourteam', compact('getteams'));
    }
    public function aboutus(Request $request)
    {
        $getaboutus = Aboutus::first();
        return view('web.aboutus', compact('getaboutus'));
    }
    // contact-us
    public function contactindex(Request $request)
    {
        $timedata = Time::where('day', date('l'))->first();
        return view('web.contactus', compact('timedata'));
    }
    public function contactstore(Request $request)
    {
        $contact = new Contact;
        $contact->firstname = $request->fname;
        $contact->lastname = $request->lname;
        $contact->email = $request->email;
        $contact->message = $request->message;
        $contact->save();
        return redirect()->back()->with('success', trans('messages.success'));
    }
    // testimonials
    public function testimonials(Request $request)
    {
        $testimonials = Ratting::with('user_info')->orderByDesc('ratting.id')->paginate(9);
        return view('web.testimonials', compact('testimonials'));
    }
    // refund-policy
    public function refundpolicy(Request $request)
    {
        $getrefundpolicy = RefundPolicy::first();
        return view('web.refundpolicy', compact('getrefundpolicy'));
    }
    //subscribe
    public function subscribe(Request $request)
    {
        $request->validate(['subscribe_email' => ['required', 'email:rfc', 'max:254']]);
        $email = strtolower(trim($request->subscribe_email));
        if (!Subscribe::whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            Subscribe::create(['email' => $email]);
        }
        return redirect()->back()->with('success', trans('messages.success'));
    }
}
