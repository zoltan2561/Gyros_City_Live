<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\helper;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Item;
use App\Models\Addons;
use App\Models\AddonsGroup;
use App\Models\ItemImages;
use App\Models\Cart;
use App\Models\Extra;
use App\Models\GlobalExtras;
use App\Models\Tax;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ItemController extends Controller
{


    //ma nem elérhető uj controllere
    public function toggleTodayUnavailable(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $item = Item::findOrFail($request->id);
        $today = date('Y-m-d');

        if ((int)$item->today_unavailable === 0) {
            // → Ha most jelölik be: állítsuk "ma nem elérhető"-re
            $item->today_unavailable = 1;
            $item->unavailable_date = $today;
            $item->item_status = 0; // inaktív lesz ma
        } else {
            // → Ha levesszük a pipát: újra elérhető
            $item->today_unavailable = 0;
            $item->unavailable_date = null;
            $item->item_status = 1; // aktív újra
        }

        $item->save();

        return response()->json([
            'success' => true,
            'today_unavailable' => (int)$item->today_unavailable,
            'item_status' => (int)$item->item_status
        ]);
    }



    public function index(Request $request)
    {
        $getitem = Item::with('category_info', 'item_image')
            ->whereHas('category_info', fn ($query) => $query->where('is_deleted', 2))
            ->orderBy('reorder_id')->orderByDesc('id')->get();
        $categories = Category::where('is_deleted', 2)->orderBy('reorder_id')->get();
        return view('admin.item.item', compact('getitem', 'categories'));
    }

    public function quickUpdate(Request $request)
    {
        abort_if(env('Environment') === 'sendbox', 403, 'Bemutató módban nem menthető.');
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:item,id'],
            'version' => ['required', 'string', 'size:64'],
            'item_name' => ['required', 'string', 'max:255'],
            'cat_id' => ['required', 'integer', \Illuminate\Validation\Rule::exists('categories', 'id')->where('is_deleted', 2)],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'original_price' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'availability' => ['required', \Illuminate\Validation\Rule::in(['active', 'paused', 'today'])],
            'is_featured' => ['required', 'boolean'],
        ]);
        if (($data['original_price'] ?? 0) > 0 && $data['original_price'] < $data['price']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['original_price' => 'Az eredeti ár nem lehet kisebb az eladási árnál.']);
        }
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $item = Item::whereKey($data['id'])->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals($item->edit_token, $data['version']), 409,
                'A terméket közben módosították. Frissítsd a listát, majd ellenőrizd az adatokat.');
            if ((int) $item->cat_id !== (int) $data['cat_id']) $item->subcat_id = null;
            $item->cat_id = $data['cat_id'];
            $item->item_name = trim($data['item_name']);
            $item->price = $data['price'];
            $item->original_price = $data['original_price'] ?? 0;
            $item->discount_percentage = $item->original_price > 0
                ? round(100 - $item->price * 100 / $item->original_price, 1) : 0;
            $item->is_featured = $data['is_featured'] ? 1 : 2;
            $item->item_status = ['active' => 1, 'paused' => 2, 'today' => 0][$data['availability']];
            $item->today_unavailable = $data['availability'] === 'today' ? 1 : 0;
            $item->unavailable_date = $data['availability'] === 'today' ? now('Europe/Budapest')->toDateString() : null;
            $item->save();
            // Preserve existing cart snapshots and customisations; only the selected product is edited.
            return response()->json(['success' => true, 'message' => 'A termék módosításait mentettük.']);
        });
    }
    public function additem()
    {
        $getcategory = Category::where('is_available', '1')->orderBy('reorder_id')->get();
        $getaddongroup = AddonsGroup::where('is_deleted', 2)->where('is_available', 1)->orderBy('reorder_id')->get();
        $getaddon = Addons::select('id', 'addongroup_id', 'name', 'price')->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
        foreach ($getaddongroup as $addons_group) {
            $addons_group->availableAddons = $getaddon->where('addongroup_id', $addons_group->id);
        }
        $gettax = Tax::where('is_available', '1')->orderBy('reorder_id')->get();
        $globalextras = GlobalExtras::where('is_available', 1)->orderBy('reorder_id')->get();
        return view('admin.item.additem', compact('getcategory', 'getaddongroup', 'getaddon', 'gettax', 'globalextras'));
    }
    public function edititem($id)
    {
        $getitem = Item::with('extras')->find($id);
        $getitemimages = ItemImages::where('item_id', $id)->orderByDesc('id')->get();
        $getcategory = Category::where('is_available', '1')->orderBy('reorder_id')->get();
        $getsubcategory = Subcategory::where('cat_id', $getitem->cat_id)->where('is_available', '1')->orderBy('reorder_id')->get();
        $getaddongroup = AddonsGroup::where('is_deleted', 2)->where('is_available', 1)->orderBy('reorder_id')->get();
        $getaddon = Addons::select('id', 'addongroup_id', 'name', 'price')->where('is_deleted', 2)->where('is_available', 1)->orderByDesc('id')->get();
        foreach ($getaddongroup as $addons_group) {
            $addons_group->availableAddons = $getaddon->where('addongroup_id', $addons_group->id);
        }
        $gettax = Tax::where('is_available', '1')->orderBy('reorder_id')->get();
        $globalextras = GlobalExtras::where('is_available', 1)->orderBy('reorder_id')->get();
        return view('admin.item.edititem', compact('getitem', 'getcategory', 'getsubcategory', 'getaddongroup', 'getaddon', 'gettax', 'globalextras', 'getitemimages'));
    }
    public function store(Request $request)
    {
        $item = new Item();
        $item->cat_id = $request->cat_id;
        $item->subcat_id = $request->subcat_id == "" ? "" : $request->subcat_id;
        $item->preparation_time = $request->preparation_time;
        $item->addons_id = $request->addongroup_id != "" ? @implode(",", $request->addongroup_id) : null;
        $item->item_name = $request->item_name;
        $item->slug = $this->getitemslug($request->item_name, '');
        $item->item_type = $request->item_type;
        $item->has_extras = $request->has_extras;
        if ($request->original_price == "") {
            $discount =  0;
        } else {
            $discount =  $request->original_price > 0 ? number_format(100 - ($request->price * 100) / $request->original_price, 1) : 0;
        }
        $item->price = helper::number_format($request->price);
        $item->original_price = helper::number_format($request->original_price == null ? 0 : $request->original_price);
        $item->discount_percentage = $discount;
        $item->item_description = $request->description;
        $item->item_allergens = $request->allergens;
        $item->tax = $request->tax != "" ? @implode(",", $request->tax) : '';
        $item->video_url = $request->video_url;
        $item->avg_ratting = 0;
        if ($item->save()) {
            if ($request->has_extras == 1 && $request->extras_name != "") {
                foreach ($request->extras_name as $key => $no) {
                    if (@$no != "" && @$request->extras_price[$key] != "") {
                        $extras = new Extra();
                        $extras->item_id = $item->id;
                        $extras->name = $no;
                        $extras->price = $request->extras_price[$key];
                        $extras->save();
                    }
                }
            }
            foreach ($request->file('image') as $img) {
                $itemimage = new ItemImages;
                $image = 'item-' . uniqid() . '.' . $img->getClientOriginalExtension();
                $img->move(env('ASSETSPATHURL') . 'admin-assets/images/item', $image);
                $itemimage->item_id = $item->id;
                $itemimage->image = $image;
                $itemimage->save();
            }
            return redirect('admin/item')->with('success', trans('messages.success'));
        } else {
            return redirect()->back()->with('error', trans('messages.wrong'));
        }
    }
    public function storeimages(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'file.*' => 'required'
        ]);
        $error_array = array();
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            if ($request->hasFile('file')) {
                $files = $request->file('file');
                foreach ($files as $file) {
                    $itemimage = new ItemImages;
                    $image = 'item-' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(env('ASSETSPATHURL') . 'admin-assets/images/item', $image);
                    $itemimage->item_id = $request->itemid;
                    $itemimage->image = $image;
                    $itemimage->save();
                }
            }
            $success_output = trans('messages.success');
        }
        $output = array('error'     =>  $error_array, 'success'   =>  $success_output);
        echo json_encode($output);
    }
    public function showimage(Request $request)
    {
        $getitem = ItemImages::where('id', $request->id)->first();
        if ($getitem->image) {
            $getitem->img = url(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $getitem->image);
        }
        return response()->json(['ResponseCode' => 1, 'ResponseText' => trans('messages.success'), 'ResponseData' => $getitem], 200);
    }
    public function update(Request $request)
    {
        Cart::where('item_id', $request->id)->delete();
        $item = Item::find($request->id);
        $item->cat_id = $request->cat_id;
        $item->subcat_id = $request->subcat_id == "" ? "" : $request->subcat_id;
        $item->preparation_time = $request->preparation_time;
        $item->addons_id = $request->addongroup_id != "" ? @implode(",", $request->addongroup_id) : null;
        $item->item_type = $request->item_type;
        $item->has_extras = $request->has_extras;
        if ($request->original_price == "") {
            $discount =  0;
        } else {
            $discount =  $request->original_price > 0 ? number_format(100 - ($request->price * 100) / $request->original_price, 1) : 0;
        }
        $item->price = helper::number_format($request->price);
        $item->original_price = helper::number_format($request->original_price == null ? 0 : $request->original_price);
        $item->discount_percentage = $discount;
        $item->item_name = $request->item_name;
        $item->slug = $this->getitemslug($request->item_name, $request->id);;
        $item->item_description = $request->description;
        $item->item_allergens = $request->allergens;
        $item->tax = $request->tax != "" ? @implode(",", $request->tax) : '';
        $item->video_url = $request->video_url;
        if ($item->save()) {
            if ($request->has_extras == 1 && $request->extras_name != "") {
                $extras_id = $request->extras_id;
                foreach ($request->extras_name as $key => $no) {
                    if (@$no != "" && @$request->extras_price[$key] != "") {
                        if (@$extras_id[$key] == "") {
                            $extras = new Extra();
                            $extras->item_id = $item->id;
                            $extras->name = $no;
                            $extras->price = $request->extras_price[$key];
                            $extras->save();
                        } else if (@$extras_id[$key] != "") {
                            Extra::where('id', @$extras_id[$key])->update(['name' => $request->extras_name[$key], 'price' => $request->extras_price[$key]]);
                        }
                    }
                }
            }
            if ($request->has_extras == 2) {
                Extra::where('item_id', $item->id)->delete();
            }
            return redirect('admin/item')->with('success', trans('messages.success'));
        } else {
            return redirect()->back()->with('error', trans('messages.wrong'));
        }
    }
    public function reorder_item(Request $request)
    {
        abort_if(env('Environment') === 'sendbox', 403);
        $data = $request->validate([
            'order' => ['required', 'array', 'min:1', 'max:10000'],
            'order.*.id' => ['required', 'integer', 'distinct', 'exists:item,id'],
            'order.*.position' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            foreach ($data['order'] as $row) {
                Item::whereKey($row['id'])->update(['reorder_id' => $row['position']]);
            }
        });
        return response()->json(['status' => 1, 'msg' => 'A sorrendet mentettük.']);
    }


    public function updateimage(Request $request)
    {
        $validation = Validator::make($request->all(), ['image' => 'image']);
        $error_array = array();
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $itemimage = ItemImages::find($request->id);
            if ($request->hasFile('image')) {
                if (file_exists(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $itemimage->image)) {
                    unlink(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $itemimage->image);
                }
                $image = 'item-' . uniqid() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(env('ASSETSPATHURL') . 'admin-assets/images/item', $image);
                $itemimage->image = $image;
            }
            $itemimage->save();
            $success_output = trans('messages.success');
        }
        $output = array('error' => $error_array, 'success' => $success_output);
        echo json_encode($output);
    }
    public function getitemslug($item_name, $id)
    {
        $slug = Str::slug($item_name, '-');
        $checkslug = Item::where('slug', $slug);
        if ($id != "") {
            $checkslug = $checkslug->where('id', '!=', $id);
        }
        $checkslug = $checkslug->first();
        if (!empty($checkslug)) {
            $lastid = Item::select('id')->orderByDesc('id')->first();
            $slug .= '-' . $lastid->id;
        }
        return $slug;
    }
    public function status(Request $request)
    {
        $UpdateDetails = Item::where('id', $request->id)->update(['item_status' => $request->status]);
        if ($UpdateDetails) {
            Cart::where('item_id', $request->id)->delete();
            return 1;
        } else {
            return 0;
        }
    }
    public function delete(Request $request)
    {
        $updatedetails = Item::where('id', $request->id)->delete();
        if ($updatedetails) {
            Cart::where('item_id', $request->id)->delete();
            Extra::where('item_id', $request->id)->delete();
            $itemimages = ItemImages::where('item_id', $request->id)->get();
            foreach ($itemimages as $image) {
                if (file_exists(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $image->image)) {
                    unlink(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $image->image);
                }
                $image->delete();
            }
            return 1;
        } else {
            return 0;
        }
    }
    public function destroyimage(Request $request)
    {
        $getitemimages = ItemImages::where('item_id', $request->item_id)->count();
        if ($getitemimages > 1) {
            $itemimage = ItemImages::where('id', $request->id)->first();
            if (file_exists(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $itemimage->image)) {
                unlink(env('ASSETSPATHURL') . 'admin-assets/images/item/' . $itemimage->image);
            }
            $itemimage->delete();
            if ($itemimage) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return 2;
        }
    }
    public function featured(Request $request)
    {
        $updatedata = Item::where('id', $request->id)->update(['is_featured' => $request->status]);
        if ($updatedata) {
            return 1;
        } else {
            return 0;
        }
    }
    public function subcategories(Request $request)
    {
        $data = Subcategory::where('cat_id', $request->id)->orderBy('reorder_id')->where('is_available', 1)->get();
        return response()->json(['status' => 1, 'message' => trans('messages.success'), 'data' => $data], 200);
    }
    public function getextras()
    {
        $globalextras = GlobalExtras::where('is_available', 1)->orderBy('reorder_id')->get();
        return response()->json(['status' => 1, 'msg' => trans('messages.success'), 'responsdata' => $globalextras], 200);
    }
    public function deleteextras(Request $request)
    {
        $checkextracount = Extra::where('item_id', $request->item_id)->count();
        if ($checkextracount > 1) {
            $deletedata = Extra::where('id', $request->id)->delete();
            if ($deletedata) {
                Cart::where('item_id', $request->item_id)->delete();
                return 1;
            } else {
                return 0;
            }
        } else {
            return 2;
        }
    }
}
