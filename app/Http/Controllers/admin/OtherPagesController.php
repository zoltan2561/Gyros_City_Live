<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Gallery;
use App\Models\Team;
use App\Models\Faq;
use App\Models\Subscribe;
use App\Models\Tutorial;
use Illuminate\Support\Facades\Validator;

class OtherPagesController extends Controller
{
    // OUR-TEAM
    public function our_team_index(Request $request)
    {
        $getteams = Team::orderBy('reorder_id')->get();
        return view('admin.team.index', compact('getteams'));
    }
    public function our_team_add(Request $request)
    {
        return view('admin.team.add');
    }
    public function our_team_store(Request $request)
    {
        $image = 'team-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
        $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about/', $image);
        $team = new Team;
        $team->image = $image;
        $team->name = $request->name;
        $team->designation = $request->designation;
        $team->fb = $request->fb;
        $team->youtube = $request->youtube;
        $team->insta = $request->insta;
        $team->twitter = $request->twitter;
        $team->description = $request->description;
        $team->save();
        return redirect('admin/our-team')->with('success', trans('messages.success'));
    }
    public function our_team_show(Request $request)
    {
        $teamdata = Team::find($request->id);
        return view('admin.team.edit', compact('teamdata'));
    }
    public function our_team_update(Request $request)
    {
        $team = Team::find($request->id);
        if ($request->file('image') != "") {
            if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $team->image)) {
                unlink(storage_path() . "/app/public/admin-assets/images/about/" . $team->image);
            }
            $image = 'team-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about', $image);
            $team->image = $image;
            $team->save();
        }
        $team->name = $request->name;
        $team->designation = $request->designation;
        $team->fb = $request->fb;
        $team->youtube = $request->youtube;
        $team->insta = $request->insta;
        $team->twitter = $request->twitter;
        $team->description = $request->description;
        $team->save();
        return redirect('admin/our-team')->with('success', trans('messages.success'));
    }
    public function our_team_delete(Request $request)
    {
        $team = Team::find($request->id);
        if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $team->image)) {
            unlink(storage_path() . "/app/public/admin-assets/images/about/" . $team->image);
        }
        if ($team->delete()) {
            return 1;
        } else {
            return 0;
        }
    }
    public function reorder_our_team(Request $request)
    {
        $getourteam = Team::all();
        foreach ($getourteam as $ourteam) {
            foreach ($request->order as $order) {
                $ourteam = Team::where('id', $order['id'])->first();
                $ourteam->reorder_id = $order['position'];
                $ourteam->save();
            }
        }
        return response()->json(['status' => 1, 'msg' => 'Update Successfully!!'], 200);
    }
    // tutorial
    public function tutorial_index(Request $request)
    {
        $gettutorials = Tutorial::orderBydesc('id')->get();
        return view('admin.tutorial.index', compact('gettutorials'));
    }
    public function tutorial_add(Request $request)
    {
        return view('admin.tutorial.add');
    }
    public function tutorial_store(Request $request)
    {
        $image = 'tutorial-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
        $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about/', $image);
        $team = new Tutorial;
        $team->image = $image;
        $team->title = $request->title;
        $team->description = $request->description;
        $team->save();
        return redirect('admin/tutorial')->with('success', trans('messages.success'));
    }
    public function tutorial_show(Request $request)
    {
        $tutorialdata = Tutorial::find($request->id);
        return view('admin.tutorial.edit', compact('tutorialdata'));
    }
    public function tutorial_update(Request $request)
    {
        $team = Tutorial::find($request->id);
        if ($request->file('image') != "") {
            if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $team->image)) {
                unlink(storage_path() . "/app/public/admin-assets/images/about/" . $team->image);
            }
            $image = 'tutorial-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about', $image);
            $team->image = $image;
            $team->save();
        }
        $team->title = $request->title;
        $team->description = $request->description;
        $team->save();
        return redirect('admin/tutorial')->with('success', trans('messages.success'));
    }
    public function tutorial_delete(Request $request)
    {
        $team = Tutorial::find($request->id);
        if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $team->image)) {
            unlink(storage_path() . "/app/public/admin-assets/images/about/" . $team->image);
        }
        if ($team->delete()) {
            return 1;
        } else {
            return 0;
        }
    }
    // faq
    public function faq_index(Request $request)
    {
        $getfaqs = Faq::orderBy('reorder_id')->get();
        return view('admin.faq.index', compact('getfaqs'));
    }
    public function faq_add(Request $request)
    {
        return view('admin.faq.add');
    }
    public function faq_store(Request $request)
    {
        $team = new Faq;
        $team->title = $request->title;
        $team->description = $request->description;
        $team->save();
        return redirect('admin/faq')->with('success', trans('messages.success'));
    }
    public function faq_show(Request $request)
    {
        $faqdata = Faq::find($request->id);
        return view('admin.faq.edit', compact('faqdata'));
    }
    public function faq_update(Request $request)
    {
        $faq = Faq::find($request->id);
        $faq->title = $request->title;
        $faq->description = $request->description;
        $faq->save();
        return redirect('admin/faq')->with('success', trans('messages.success'));
    }
    public function faq_delete(Request $request)
    {
        $faq = Faq::find($request->id);
        if ($faq->delete()) {
            return 1;
        } else {
            return 0;
        }
    }
    public function reorder_faq(Request $request)
    {
        $getfaqs = Faq::all();
        foreach ($getfaqs as $faq) {
            foreach ($request->order as $order) {
                $faq = Faq::where('id', $order['id'])->first();
                $faq->reorder_id = $order['position'];
                $faq->save();
            }
        }
        return response()->json(['status' => 1, 'msg' => 'Update Successfully!!'], 200);
    }
    // gallery
    public function gallery_index(Request $request)
    {
        $getgalleries = Gallery::orderBydesc('id')->get();
        return view('admin.gallery.index', compact('getgalleries'));
    }
    public function gallery_add(Request $request)
    {
        return view('admin.gallery.add');
    }
    public function gallery_store(Request $request)
    {
        foreach ($request->image as $img) {
            $image = 'gallery-' . uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(env('ASSETSPATHURL') . 'admin-assets/images/about/', $image);
            $team = new Gallery;
            $team->image = $image;
            $team->save();
        }
        return redirect('admin/gallery')->with('success', trans('messages.success'));
    }
    public function gallery_show(Request $request)
    {
        $gallerydata = Gallery::find($request->id);
        return view('admin.gallery.edit', compact('gallerydata'));
    }
    public function gallery_update(Request $request)
    {
        $gallery = Gallery::find($request->id);
        if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $gallery->image)) {
            unlink(storage_path() . "/app/public/admin-assets/images/about/" . $gallery->image);
        }
        $image = 'gallery-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
        $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about', $image);
        $gallery->image = $image;
        $gallery->save();
        return redirect('admin/gallery')->with('success', trans('messages.success'));
    }
    public function gallery_delete(Request $request)
    {
        $gallery = Gallery::find($request->id);
        if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $gallery->image)) {
            unlink(storage_path() . "/app/public/admin-assets/images/about/" . $gallery->image);
        }
        if ($gallery->delete()) {
            return 1;
        } else {
            return 0;
        }
    }

    public function subscribe()
    {
        $list = Subscribe::all();
        return view('admin.subscribe.subscribe', compact('list'));
    }
}
