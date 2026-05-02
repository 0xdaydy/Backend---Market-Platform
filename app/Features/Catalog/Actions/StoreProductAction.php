<?php

namespace App\Features\Catalog\Actions;

use App\Features\Catalog\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class StoreProductAction
{
    public function __invoke(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json($product->load('category'), 201);
    }
}
