<?php

namespace App\Features\Catalog\Actions;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class ShowCategoryAction
{
    public function __invoke(Category $category): JsonResponse
    {
        return response()->json($category->load('children', 'parent'));
    }
}
