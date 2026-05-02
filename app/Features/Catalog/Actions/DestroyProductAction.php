<?php

namespace App\Features\Catalog\Actions;

use App\Models\Product;
use Illuminate\Http\JsonResponse;

class DestroyProductAction
{
    public function __invoke(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }
}
