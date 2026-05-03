<?php

namespace App\Features\Catalog;

use App\Features\Catalog\Requests\StoreCategoryRequest;
use App\Features\Catalog\Requests\StoreProductRequest;
use App\Features\Catalog\Requests\UpdateCategoryRequest;
use App\Features\Catalog\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController
{
    public function indexCategories(): JsonResponse
    {
        $roots = Category::with('children.children')->whereNull('parent_id')->get();

        return response()->json(['data' => $roots]);
    }

    public function showCategory(Category $category): JsonResponse
    {
        return response()->json($category->load('children', 'parent'));
    }

    public function storeCategory(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json($category->load('parent'), 201);
    }

    public function updateCategory(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json($category->fresh('parent'));
    }

    public function destroyCategory(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function categoryProducts(Category $category): JsonResponse
    {
        return response()->json($category->products()->paginate(request()->input('per_page', 15)));
    }

    public function indexProducts(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function showProduct(Product $product): JsonResponse
    {
        return response()->json($product->load('category'));
    }

    public function storeProduct(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json($product->load('category'), 201);
    }

    public function updateProduct(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json($product->fresh('category'));
    }

    public function destroyProduct(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
