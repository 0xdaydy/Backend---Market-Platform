<?php

namespace App\Features\Catalog\Actions;

use App\Features\Catalog\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class UpdateCategoryAction
{
    public function __invoke(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        return response()->json($category->fresh('parent'));
    }
}
