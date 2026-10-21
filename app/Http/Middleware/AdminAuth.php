<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Models\Roles;
use Session;
use App\Helpers\helper;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        helper::language();

        if (Auth::user() && in_array(Auth::user()->type,[1,4])){
            return $next($request);
        }
        // A customer may still have an admin tab polling; denial must preserve their login.
        return redirect('admin');
    }
}
