<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\Signage\SignageExport;
use Illuminate\Http\Request;

class SignageExportController extends Controller
{
    public function __invoke(int $id, Request $request, SignageExport $exports)
    {
        return response()->json($exports->start($id, $request->user()->id), 202)->header('Cache-Control', 'private, no-store');
    }

    public function latest(int $id, Request $request, SignageExport $exports)
    {
        return response()->json(['job'=>$exports->latest($id, $request->user()->id)])->header('Cache-Control', 'private, no-store');
    }

    public function status(string $job, Request $request, SignageExport $exports)
    {
        return response()->json($exports->status($job, $request->user()->id))->header('Cache-Control', 'private, no-store');
    }

    public function download(string $job, Request $request, SignageExport $exports)
    {
        $state = $exports->status($job, $request->user()->id);
        return response()->download($exports->downloadPath($job, $request->user()->id), $state['filename'],
            ['Content-Type'=>'video/mp4', 'Cache-Control'=>'private, no-store']);
    }
}