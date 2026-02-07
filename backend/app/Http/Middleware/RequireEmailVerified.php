<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireEmailVerified
{
    /**
     * Handle an incoming request.
     * Prevents users with unverified emails from accessing critical features:
     * - Creating listings
     * - Creating requests
     * - Accepting deals
     * - Sending messages
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if email is verified
        if (!$user->email_verified && !$user->email_verified_at) {
            return response()->json([
                'error' => 'Email verification required',
                'message' => 'Please verify your email address before accessing this feature',
                'email' => $user->email,
            ], 403);
        }

        return $next($request);
    }
}
