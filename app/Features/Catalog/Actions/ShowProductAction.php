<?php

namespace App\Features\Catalog\Actions;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ShowProductAction
{
    public function __invoke(Product $product): JsonResponse
    {
        return response()->json($product->load('category'));
    }
}
