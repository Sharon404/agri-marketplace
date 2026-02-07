<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link to email
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Generate a unique token and store in database
        $user = User::where('email', $request->email)->first();
        $token = Str::random(64);

        // In production, store this with expiration time
        // For now, we'll use a simple implementation
        $user->update([
            'reset_token' => $token,
            'reset_token_expires_at' => now()->addHours(1),
        ]);

        // In production, send email with token
        // Mail::send('emails.reset-password', ['token' => $token], function ($message) use ($user) {
        //     $message->to($user->email)->subject('Reset Your Password');
        // });

        return response()->json([
            'message' => 'Password reset link sent to your email',
            'token' => $token, // In production, send via email only
            'expires_in' => 3600, // 1 hour
        ]);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Verify token and expiration
        if (!$user->reset_token || $user->reset_token !== $request->token) {
            return response()->json(['error' => 'Invalid reset token'], 422);
        }

        if ($user->reset_token_expires_at < now()) {
            return response()->json(['error' => 'Reset token has expired'], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully',
            'email' => $user->email,
        ]);
    }

    /**
     * Change password (authenticated user)
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }
}
