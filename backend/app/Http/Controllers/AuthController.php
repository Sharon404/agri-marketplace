<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

        $user->activation_token = Str::random(64);
        $user->save();

        // Send activation email (you can implement this later)
        // Mail::to($user->email)->send(new ActivationEmail($user));

        return response()->json([
            'message' => 'User registered successfully. Please check your email for activation link.',
            'user' => $user,
            'activation_url' => url('/api/activate/' . $user->activation_token),
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        // Check if account is activated
        if (!$user->activated_at) {
            return response()->json(['error' => 'Account not activated. Please check your email for activation link.'], 401);
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

    public function activate($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid activation token'], 400);
        }

        if ($user->activated_at) {
            return response()->json(['message' => 'Account already activated'], 200);
        }

        $user->update([
            'activation_token' => null,
            'activated_at' => now(),
            'email_verified' => true,
        ]);

        return response()->json(['message' => 'Account activated successfully. You can now login.'], 200);
    }
}
