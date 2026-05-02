<?php

namespace App\Features\Catalog\Actions;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexProductAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = Product::query()->with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }
}
