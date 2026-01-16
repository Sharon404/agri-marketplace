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
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => 'required|in:farmer,buyer,admin',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // For testing without database - using mock data
        $user = [
            'id' => rand(1, 1000),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'email_verified' => true,
            'phone_verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $token = 'mock_jwt_token_' . time() . '_' . $request->role;

        return response()->json([
            'message' => 'Registration successful',
            'user' => $user,
            'token' => $token,
        ], 201);

        /*
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified' => true, // Auto-verify for development
            'activated_at' => now(), // Auto-activate for development
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
        */
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // For testing without database - mock login
        $user = [
            'id' => 1,
            'name' => 'Test User',
            'email' => $request->email,
            'phone' => '+254700000000',
            'role' => $this->getRoleFromEmail($request->email),
            'email_verified' => true,
            'phone_verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $token = 'mock_jwt_token_' . time() . '_' . $user['role'];

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);

        /*
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
        */
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
