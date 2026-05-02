<?php

namespace App\Features\Catalog\Actions;

use App\Features\Catalog\Requests\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class UpdateProductAction
{
    public function __invoke(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json($product->fresh('category'));
    }
}
