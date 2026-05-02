<?php

namespace App\Features\Catalog\Actions;

use App\Features\Catalog\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class StoreCategoryAction
{
    public function __invoke(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return response()->json($category->load('parent'), 201);
    }
}
