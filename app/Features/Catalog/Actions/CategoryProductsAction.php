<?php

namespace App\Features\Catalog\Actions;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryProductsAction
{
    public function __invoke(Category $category): JsonResponse
    {
        return response()->json($category->products()->paginate(request()->input('per_page', 15)));
    }
}
