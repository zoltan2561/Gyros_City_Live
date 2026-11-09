<?php
namespace App\Http\Controllers\Signage;
use App\Http\Controllers\Controller;
class PlayerController extends Controller {
    public function index() {
        if(!str_ends_with(request()->getPathInfo(),'/'))return redirect(url('signage').'/');
        return response()->view('signage.player')->header('Cache-Control','no-store')
            ->header('Referrer-Policy','no-referrer')
            ->header('Content-Security-Policy',"default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' blob: data:; media-src 'self' blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'");
    }
    public function worker() {
        return response()->file(public_path('signage/sw.js'),['Content-Type'=>'application/javascript','Cache-Control'=>'no-cache']);
    }
}
