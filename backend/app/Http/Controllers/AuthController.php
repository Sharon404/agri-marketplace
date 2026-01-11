<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:farmer,buyer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Send verification codes (simplified - in production use queues/notifications)
        if ($request->email) {
            Verification::create([
                'user_id' => $user->id,
                'type' => 'email',
                'code' => rand(100000, 999999),
                'expires_at' => now()->addMinutes(15),
            ]);
        }

        if ($request->phone) {
            Verification::create([
                'user_id' => $user->id,
                'type' => 'phone',
                'code' => rand(100000, 999999),
                'expires_at' => now()->addMinutes(15),
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Check if account is verified
        if (!$user->email_verified && !$user->phone_verified) {
            return response()->json(['error' => 'Account not verified. Please verify your email or phone first.'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'message' => 'Token refreshed',
            'token' => $token,
        ]);
    }

    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:email,phone',
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = auth()->user();
        $verification = Verification::where('user_id', $user->id)
            ->where('type', $request->type)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$verification) {
            return response()->json(['error' => 'Invalid or expired verification code'], 400);
        }

        $verification->update(['verified_at' => now()]);

        if ($request->type === 'email') {
            $user->update(['email_verified' => true]);
        } else {
            $user->update(['phone_verified' => true]);
        }

        return response()->json(['message' => ucfirst($request->type) . ' verified successfully']);
    }
}
