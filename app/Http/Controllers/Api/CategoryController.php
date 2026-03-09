<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query();
        if ($request->has('merchant_id')) {
            $categories->where('merchant_id', $request->merchant_id);
        }
        return response()->json($categories->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $merchant = $request->user()->merchant;
        if (!$merchant) return response()->json(['message' => 'Unauthorized.'], 403);

        $category = Category::create([
            'merchant_id' => $merchant->id,
            'name' => $request->name,
        ]);

        return response()->json($category, 201);
    }
}
