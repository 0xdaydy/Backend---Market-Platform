<?php

namespace App\Features\Catalog\Actions;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class DestroyCategoryAction
{
    public function __invoke(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
