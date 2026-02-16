<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SellerProfileResource;
use App\Models\Order;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function profile(Request $request)
    {
        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        return new SellerProfileResource($profile);
    }

    public function updateProfile(Request $request)
    {
        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $profile->update($data);

        return new SellerProfileResource($profile);
    }

    public function orders(Request $request)
    {
        $profile = $request->user()->sellerProfile;

        if (!$profile) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $orders = Order::whereHas('items', function ($query) use ($profile) {
                $query->where('seller_id', $profile->id);
            })
            ->with(['items.product', 'payment'])
            ->latest()
            ->paginate(20);

        return OrderResource::collection($orders);
    }
}
