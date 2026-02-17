<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAdminRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SellerVerificationRequest;
use App\Http\Resources\UserResource;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        // DEBUG: Log that we received the request
        error_log("DEBUG: register() called");
        
        try {
            $data = $request->validated();
            error_log("DEBUG: validation passed");
            
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => $data['role'] ?? 'buyer',
                'status' => 'active',
            ]);
            
            error_log("DEBUG: user created with id " . $user->id);
            
            // Only support buyer registration via /register
            if ($user->role !== 'buyer') {
                $user->delete();
                return response()->json([
                    'message' => 'Sellers must use the /register/seller endpoint',
                    'error' => 'invalid_role',
                ], 400);
            }

            $token = $user->createToken('api')->plainTextToken;
            error_log("DEBUG: token created");
            
            return response()->json([
                'message' => 'Registration successful',
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
            ], 201);
            
        } catch (\Throwable $e) {
            error_log("DEBUG: Exception in register - " . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerSeller(SellerVerificationRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => 'seller',
            'status' => 'active',
        ]);

        // Create seller profile with PENDING verification
        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => $data['business_name'],
            'description' => $data['description'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'business_address' => $data['business_address'],
            'tax_id' => $data['tax_id'],
            'national_id' => $data['national_id'],
            'bank_name' => $data['bank_name'],
            'bank_account_name' => $data['bank_account_name'],
            'bank_account_number' => $data['bank_account_number'],
            'verification_status' => 'pending', // High scrutiny: requires admin approval
            'commission_rate' => null,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Seller registration submitted. Awaiting verification.',
            'user' => new UserResource($user->load('sellerProfile')),
            'access_token' => $token,
            'token_type' => 'bearer',
            'status' => 'pending_verification',
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'Account is not active.',
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user->load('sellerProfile')),
            'access_token' => $token,
            'token_type' => 'bearer',
        ]);
    }

    public function createAdmin(CreateAdminRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => 'admin',
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Admin user created successfully',
            'user' => new UserResource($user),
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
