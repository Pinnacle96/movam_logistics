<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $merchant = $request->user()->merchant;
        $search = $request->query('search');
        $category_id = $request->query('category_id');

        $query = Product::where('merchant_id', $merchant->id)->with('category');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category_id) {
            $query->where('category_id', $category_id);
        }

        return response()->json($query->latest()->get());
    }

    public function publicIndex(Request $request)
    {
        $search = $request->query('search');
        $category_id = $request->query('category_id');
        $merchant_id = $request->query('merchant_id');

        $query = Product::where('is_available', true)->with(['category', 'merchant']);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category_id) {
            $query->where('category_id', $category_id);
        }

        if ($merchant_id) {
            if (is_numeric($merchant_id)) {
                $query->where('merchant_id', $merchant_id);
            } else {
                $merchant = \App\Models\Merchant::where('slug', $merchant_id)->first();
                if ($merchant) {
                    $query->where('merchant_id', $merchant->id);
                } else {
                    // If merchant not found by slug, return empty
                    return response()->json([]);
                }
            }
        }

        return response()->json($query->latest()->get());
    }

    public function categories()
    {
        return response()->json(Category::all());
    }

    public function store(Request $request)
    {
        $merchant = $request->user()->merchant;

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'stock' => 'nullable|integer|min:0',
        ]);

        $data = $request->except('image');
        $data['merchant_id'] = $merchant->id;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product = Product::create($data);

        return response()->json($product->load('category'), 201);
    }

    public function update(Request $request, Product $product)
    {
        $merchant = $request->user()->merchant;

        if ($product->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
            'stock' => 'nullable|integer|min:0',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image_url) {
                $oldPath = str_replace('/storage/', 'public/', $product->image_url);
                Storage::delete($oldPath);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = Storage::url($path);
        }

        $product->update($data);

        return response()->json($product->load('category'));
    }

    public function destroy(Product $product)
    {
        $merchant = request()->user()->merchant;

        if ($product->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function toggleAvailability(Product $product)
    {
        $merchant = request()->user()->merchant;

        if ($product->merchant_id !== $merchant->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product->update(['is_available' => !$product->is_available]);

        return response()->json($product);
    }
}
