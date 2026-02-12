<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Prefer session user (web), but allow JWT-authenticated admin for API-driven access
        $user = auth()->user();

        if (!$user) {
            // Try API guard
            try {
                $apiUser = auth('api')->user();
            } catch (\Exception $e) {
                $apiUser = null;
            }

            if ($apiUser) {
                // If API user is admin, set as the current user for the request
                if ($apiUser->role === 'admin') {
                    auth()->setUser($apiUser);
                    return $next($request);
                }
            }

            return redirect()->route('login')->with('error', 'Unauthorized access');
        }

        if ($user->role !== 'admin') {
            return redirect()->route('login')->with('error', 'Unauthorized access');
        }

        return $next($request);
    }
}
