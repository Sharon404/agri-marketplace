<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CapabilityMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Checks if user has the required capability (can_buy or can_sell).
     * Falls back to role-based checks if no capability record exists.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $capability  Either 'buy' or 'sell'
     */
    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - Authentication required'
            ], 401);
        }

        // Check capability based on parameter
        $hasCapability = false;

        switch ($capability) {
            case 'buy':
                $hasCapability = $user->canBuy();
                break;
            case 'sell':
                $hasCapability = $user->canSell();
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid capability type'
                ], 500);
        }

        if (!$hasCapability) {
            // Check if suspended
            if ($user->capability && $user->capability->isSuspended()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact support.'
                ], 403);
            }

            // Not authorized
            return response()->json([
                'success' => false,
                'message' => "You do not have permission to {$capability}. Please request this capability from an administrator."
            ], 403);
        }

        return $next($request);
    }
}
