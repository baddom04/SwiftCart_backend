<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckHouseholdMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $household = $request->route('household');

        $authUser = Auth::user();

        if (!$household || (!$authUser->admin && !$authUser->memberHouseholds->contains('id', $household->id))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
