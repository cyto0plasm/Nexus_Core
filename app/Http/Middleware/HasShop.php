<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasShop
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // if only user logged in, onboarding done
        $shop = $user->shops()->first();

        if (!$shop) {
            return response()->json([
                'message' => 'Please complete onboarding first'
            ], 403);
        }

        //  attach to request — next middlewares reuse this
        $request->merge(['current_shop' => $shop]);

        return $next($request);
    }
}
