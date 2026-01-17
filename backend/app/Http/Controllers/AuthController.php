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
            'password' => 'required|string|min:6',
            'role' => 'required|in:farmer,buyer,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Create user in database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified' => true,
            'phone_verified' => false,
        ]);

        // Generate token
        $token = 'jwt_' . base64_encode($user->id . ':' . $user->email . ':' . time());

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user->makeHidden('password')->toArray(),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate token
        $token = 'jwt_' . base64_encode($user->id . ':' . $user->email . ':' . time());

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->makeHidden('password')->toArray(),
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

    private function getRoleFromEmail($email)
    {
        if (str_contains($email, 'admin')) {
            return 'admin';
        } elseif (str_contains($email, 'farmer')) {
            return 'farmer';
        } elseif (str_contains($email, 'buyer')) {
            return 'buyer';
        }
        return 'buyer'; // default role
    }
}
