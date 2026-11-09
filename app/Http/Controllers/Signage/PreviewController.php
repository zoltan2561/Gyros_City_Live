<?php

namespace App\Http\Controllers\Signage;

use App\Http\Controllers\Controller;
use App\Services\Signage\SignageCatalog;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function demo(Request $request)
    {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        $scene = ['id'=>'burgers', 'type'=>'grid', 'theme'=>'dark', 'title'=>'Burgereink', 'duration'=>15,
            'bindings'=>array_map(fn ($id) => ['id'=>'burger-'.$id,'item_id'=>$id], range(60,65))];
        if ($request->boolean('catalog')) {
            return response()->json(app(SignageCatalog::class)->snapshot($scene['bindings'], new \DateTimeImmutable()))
                ->header('Cache-Control','no-store');
        }
        return response()->view('signage.preview', compact('scene'))->header('Cache-Control','no-store');
    }
}
