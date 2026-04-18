<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file  = $request->file('image');
        $name  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path  = $file->storeAs('products', $name, 'public');

        return response()->json([
            'url'  => Storage::url($path),
            'path' => $path,
        ], 201);
    }
}
