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

        // Load users from JSON file
        $usersFile = base_path('storage/app/users.json');
        $usersData = [];
        if (file_exists($usersFile)) {
            $jsonContent = file_get_contents($usersFile);
            if ($jsonContent) {
                $usersData = json_decode($jsonContent, true);
            }
        }

        // Check if user already exists
        foreach ($usersData['users'] ?? [] as $existingUser) {
            if ($existingUser['email'] === $request->email) {
                return response()->json(['error' => 'Email already exists'], 422);
            }
        }

        // Create new user
        $userId = count($usersData['users'] ?? []) + 1;
        $user = [
            'id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified' => true,
            'phone_verified' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $usersData['users'][] = $user;

        // Save to JSON file
        file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Generate token
        $token = 'jwt_' . base64_encode($user['id'] . ':' . $user['email'] . ':' . time());

        return response()->json([
            'message' => 'Registration successful',
            'user' => collect($user)->except('password')->toArray(),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        // Load users from JSON file
        $usersFile = base_path('storage/app/users.json');
        $usersData = [];
        if (file_exists($usersFile)) {
            $jsonContent = file_get_contents($usersFile);
            if ($jsonContent) {
                $usersData = json_decode($jsonContent, true);
            }
        }

        // Find user by email
        $user = null;
        foreach ($usersData['users'] ?? [] as $existingUser) {
            if ($existingUser['email'] === $request->email) {
                $user = $existingUser;
                break;
            }
        }

        if (!$user) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Verify password
        if (!Hash::check($request->password, $user['password'])) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Generate token
        $token = 'jwt_' . base64_encode($user['id'] . ':' . $user['email'] . ':' . time());

        return response()->json([
            'message' => 'Login successful',
            'user' => collect($user)->except('password')->toArray(),
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
