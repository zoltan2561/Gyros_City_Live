<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Support\Facades\Auth;
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
        $route = $request->route();
        $protected = $request->is('admin', 'admin/*')
            || ($route && (in_array('AdminAuth', $route->gatherMiddleware(), true) || in_array(self::class, $route->gatherMiddleware(), true)));
        if (!$protected || $request->is('admin', 'admin/check-login', 'admin/forgot-password', 'admin/send-pass', 'admin/logout')
            || $request->attributes->get('admin_permissions_checked')) return $next($request);

        // Fresh database state revokes already-open sessions when access changes.
        $user = Auth::id() ? User::find(Auth::id()) : null;
        // Customer and admin share the web guard. A forbidden admin request must
        // not sign a customer out of the storefront (including a stale admin tab).
        if ($user && !in_array((int) $user->type, [1, 4], true)) {
            Auth::setUser($user);
            if (!$request->isMethod('GET')) abort(403);
            return redirect('admin');
        }
        if (!AdminPermissions::isActive($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($user && !$request->isMethod('GET')) abort(403);
            return redirect('admin');
        }
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        abort_unless($route && AdminPermissions::allowsRoute($user, $route), 403);
        $request->attributes->set('admin_permissions_checked', true);
        helper::language();
        return $next($request);
    }
}
