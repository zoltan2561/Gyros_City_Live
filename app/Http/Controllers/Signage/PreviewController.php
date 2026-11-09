<?php

namespace App\Http\Controllers\Signage;

use App\Http\Controllers\Controller;
use App\Services\Signage\SignageDemoPrograms;
use Illuminate\Http\Request;

class PreviewController extends Controller
{
    public function demo(Request $request)
    {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
        if ($request->boolean('catalog')) {
            return response()->json(app(SignageDemoPrograms::class)->build(new \DateTimeImmutable()))
                ->header('Cache-Control','no-store');
        }
        return response()->view('signage.preview')->header('Cache-Control','no-store');
    }
}
