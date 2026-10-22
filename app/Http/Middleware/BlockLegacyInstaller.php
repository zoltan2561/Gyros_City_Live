<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BlockLegacyInstaller
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('install', 'install/*', 'update', 'update/*')) {
            return response('', 404);
        }

        return $next($request);
    }
}
