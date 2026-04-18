<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsCashier
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
       $shop = $request->current_shop;

    if ($shop->pivot->role !== 'cashier') {
        return response()->json([
            'message' => 'Cashier access required'
        ], 403);
    }

        return $next($request);
    }
}
