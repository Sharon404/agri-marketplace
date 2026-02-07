<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    /**
     * Send verification code to user's email
     */
    public function sendVerification(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        
        // Generate 6-digit verification code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store code in database
        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);
        
        // TODO: Send email with verification code
        // Mail::send('emails.verify-code', ['code' => $code], function ($message) use ($user) {
        //     $message->to($user->email)->subject('Email Verification Code');
        // });
        
        return response()->json([
            'message' => 'Verification code sent to your email',
            'email' => $user->email,
        ], 200);
    }
    
    /**
     * Verify email with code
     */
    public function verifyCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        
        // Validate code
        if (!$user->verification_code || $user->verification_code !== $request->code) {
            return response()->json(['error' => 'Invalid verification code'], 422);
        }
        
        // Check if code has expired
        if ($user->verification_code_expires_at && $user->verification_code_expires_at < now()) {
            return response()->json(['error' => 'Verification code has expired'], 422);
        }
        
        // Mark email as verified
        $user->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);
        
        return response()->json([
            'message' => 'Email verified successfully',
            'user' => $user,
        ], 200);
    }
    
    /**
     * Resend verification code (rate limited to every 60 seconds)
     */
    public function resendCode(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        if ($user->email_verified) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
        
        // Check if user can request new code (not more than once per minute)
        if ($user->verification_code_sent_at && $user->verification_code_sent_at->addSeconds(60) > now()) {
            $remaining = $user->verification_code_sent_at->addSeconds(60)->diffInSeconds(now());
            return response()->json([
                'error' => 'Please wait before requesting a new code',
                'retry_after' => $remaining,
            ], 429);
        }
        
        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
            'verification_code_sent_at' => now(),
        ]);
        
        // TODO: Send email with verification code
        
        return response()->json([
            'message' => 'New verification code sent to your email',
            'email' => $user->email,
        ], 200);
    }
}
