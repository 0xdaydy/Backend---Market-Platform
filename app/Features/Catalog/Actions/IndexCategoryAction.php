<?php

namespace App\Features\Catalog\Actions;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class IndexCategoryAction
{
    public function __invoke(): JsonResponse
    {
        $roots = Category::with('children.children')->whereNull('parent_id')->get();

        return response()->json($roots);
    }
}
