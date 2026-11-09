<?php
namespace App\Http\Middleware;
use App\Models\SignageScreen;
use Closure;
class SignageDeviceAuth {
    public function handle($request, Closure $next) {
        $token=$request->bearerToken();
        abort_unless(is_string($token) && strlen($token)<=128,401);
        $screen=SignageScreen::where('token_hash',hash('sha256',$token))->first();
        abort_unless($screen,401);
        $request->attributes->set('signage_screen',$screen);
        return $next($request);
    }
}
