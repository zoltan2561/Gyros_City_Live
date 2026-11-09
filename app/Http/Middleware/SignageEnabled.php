<?php
namespace App\Http\Middleware;
use Closure;
class SignageEnabled {
    public function handle($request, Closure $next) {
        abort_unless(config('signage.enabled') || (app()->environment('local') && in_array($request->ip(), ['127.0.0.1','::1'], true)), 404);
        return $next($request);
    }
}
