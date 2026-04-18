<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $parent) => [
                'id'       => $parent->id,
                'name'     => $parent->name,
                'slug'     => $parent->slug,
                'children' => $parent->children->map(fn (Category $child) => [
                    'id'        => $child->id,
                    'name'      => $child->name,
                    'slug'      => $child->slug,
                    'parent_id' => $child->parent_id,
                ])->values(),
            ]);

        return response()->json(['data' => $categories]);
    }
}
