<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use App\Models\WhyChooseUs;
use Illuminate\Http\Request;

class WhyChooseUsController extends Controller
{
    public function index(Request $request)
    {
        $getsettings = Settings::first();
        $getwhychooseus = WhyChooseUs::orderBy('reorder_id')->get();
        return view('admin.why_choose_us.index', compact('getsettings', 'getwhychooseus'));
    }
    public function add()
    {
        return view('admin.why_choose_us.add');
    }
    public function store(Request $request)
    {
        $image = 'choose_us-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
        $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about/', $image);
        $whychooseus = new WhyChooseUs();
        $whychooseus->image = $image;
        $whychooseus->title = $request->title;
        $whychooseus->subtitle = $request->subtitle;
        $whychooseus->save();
        return redirect('admin/choose_us')->with('success', trans('messages.success'));
    }
    public function show(Request $request)
    {
        $whychooseusdata = WhyChooseUs::find($request->id);
        return view('admin.why_choose_us.edit', compact('whychooseusdata'));
    }
    public function update(Request $request)
    {
        $whychooseus = WhyChooseUs::find($request->id);
        if ($request->file('image') != "") {
            if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $whychooseus->image)) {
                unlink(storage_path() . "/app/public/admin-assets/images/about/" . $whychooseus->image);
            }
            $image = 'choose_us-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
            $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/about', $image);
            $whychooseus->image = $image;
            $whychooseus->save();
        }
        $whychooseus->title = $request->title;
        $whychooseus->subtitle = $request->subtitle;
        $whychooseus->save();
        return redirect('admin/choose_us')->with('success', trans('messages.success'));
    }
    public function delete(Request $request)
    {
        $whychooseus = WhyChooseUs::find($request->id);
        if (file_exists(storage_path() . "/app/public/admin-assets/images/about/" . $whychooseus->image)) {
            unlink(storage_path() . "/app/public/admin-assets/images/about/" . $whychooseus->image);
        }
        if ($whychooseus->delete()) {
            return 1;
        } else {
            return 0;
        }
    }
    public function reorder_choose_us(Request $request)
    {
        $getwhychooseus = WhyChooseUs::all();
        foreach ($getwhychooseus as $whychooseus) {
            foreach ($request->order as $order) {
                $whychooseus = WhyChooseUs::where('id', $order['id'])->first();
                $whychooseus->reorder_id = $order['position'];
                $whychooseus->save();
            }
        }
        return response()->json(['status' => 1, 'msg' => 'Update Successfully!!'], 200);
    }
}
