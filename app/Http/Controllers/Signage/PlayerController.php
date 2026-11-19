<?php
namespace App\Http\Controllers\Signage;
use App\Http\Controllers\Controller;
class PlayerController extends Controller {
    public function index() {
        $directory = public_path('signage');
        $css = file_get_contents($directory.'/player.css');
        foreach (['backgrounds','modern'] as $sheet) {
            $css = str_replace("@import url('".$sheet.".css');", file_get_contents($directory.'/'.$sheet.'.css'), $css);
        }
        $css = str_replace("url('reference-assets/", "url('../public/signage/reference-assets/", $css);
        $script = file_get_contents($directory.'/online-player.js');
        $nonce = base64_encode(random_bytes(18));
        return response()->view('signage.player', compact('css','script','nonce'))->header('Cache-Control','private, no-store, no-transform')
            ->header('Referrer-Policy','no-referrer')
            ->header('Content-Security-Policy',"default-src 'self'; script-src 'nonce-".$nonce."'; style-src 'self' 'unsafe-inline'; img-src 'self' blob: data:; media-src 'self' blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
    public function worker() {
        return response()->file(public_path('signage/sw.js'),['Content-Type'=>'application/javascript','Cache-Control'=>'no-cache']);
    }
    public function connectionCheck() {
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='),200,[
            'Content-Type'=>'image/gif','Cache-Control'=>'private, no-store, no-transform',
        ]);
    }
}
