<?php

namespace App\Http\Middleware;

use App\Services\GuestCartTransfer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreserveGuestCart
{
    public function __construct(private GuestCartTransfer $carts)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $wasGuest = !Auth::check();
        $guestSessionId = $request->session()->getId();
        $response = $next($request);

        if ($wasGuest) {
            $customer = $request->user();
            if ($customer && (int) $customer->type === 2 && (int) $customer->is_available === 1 && (int) $customer->is_verified === 1) {
                $this->carts->adopt($customer, $guestSessionId);
            } else {
                // Rejected accounts can also rotate the session before the controller logs them out.
                $this->carts->keepGuestSession($guestSessionId, $request->session()->getId());
            }
        }

        return $response;
    }
}
