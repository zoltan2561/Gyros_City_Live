<?php
namespace App\Http\Middleware;
use Closure;
class SignageEnabled {
    public function handle($request, Closure $next) {
        if(!config('signage.enabled') && !(app()->environment('local') && in_array($request->ip(), ['127.0.0.1','::1'], true))) {
            return response('',404)->header('Cache-Control','no-store');
        }
        return $next($request);
    }
}
