<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckStoreOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');

        if (!$store) {
            $map = $request->route('map');

            if (!$map) {
                $segment = $request->route('segment');

                if ($segment) {
                    $store = $segment->map->store;
                }
            } else {
                $store = $map->store;
            }
        }

        if (!$store || $store->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
