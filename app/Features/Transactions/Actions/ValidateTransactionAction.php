<?php

namespace App\Features\Transactions\Actions;

use App\Features\Transactions\Requests\ValidateTransactionRequest;
use App\Services\OfflinePreValidator;
use Illuminate\Http\JsonResponse;

class ValidateTransactionAction
{
    public function __invoke(ValidateTransactionRequest $request): JsonResponse
    {
        $result = OfflinePreValidator::validate($request->validated());

        $status = $result['valid'] ? 200 : 422;

        return response()->json($result, $status);
    }
}
