<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

         $user = $request->user();
          // Full access: subscribed or on trial
        if ($user->hasFullAccess()) {
            return $next($request);
        }
         // Read-only access: allow GET requests so user can see their old data
        if ($request->isMethod('GET')) {
            return $next($request);
        }

         return response()->json([
            'message' => 'Your trial has expired. Please subscribe to continue.',
            'trial_expired' => true,
        ], 403);
    }
}
