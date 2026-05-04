<?php

use App\Features\Auth\Actions\LoginAction;
use App\Features\Auth\Actions\LogoutAction;
use App\Features\Catalog\CatalogController;
use App\Features\Farmers\FarmerController;
use App\Features\Repayments\Actions\StoreRepaymentAction;
use App\Features\Repayments\CommodityController;
use App\Features\Repayments\RepaymentController;
use App\Features\Settings\SettingController;
use App\Features\Transactions\Actions\StoreTransactionAction;
use App\Features\Transactions\Actions\ValidateTransactionAction;
use App\Features\Transactions\TransactionController;
use App\Features\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', LoginAction::class)
        ->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', LogoutAction::class);

        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::get('/farmers', [FarmerController::class, 'index']);
        Route::get('/farmers/{farmer}', [FarmerController::class, 'show']);
        Route::post('/farmers', [FarmerController::class, 'store']);
        Route::put('/farmers/{farmer}', [FarmerController::class, 'update']);
        Route::delete('/farmers/{farmer}', [FarmerController::class, 'destroy']);
        Route::get('/farmers/{farmer}/debts', [FarmerController::class, 'debts']);
        Route::get('/farmers/{farmer}/transactions', [FarmerController::class, 'transactions']);

        Route::get('/categories', [CatalogController::class, 'indexCategories']);
        Route::get('/categories/{category}', [CatalogController::class, 'showCategory']);
        Route::get('/categories/{category}/products', [CatalogController::class, 'categoryProducts']);
        Route::post('/categories', [CatalogController::class, 'storeCategory']);
        Route::put('/categories/{category}', [CatalogController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [CatalogController::class, 'destroyCategory']);

        Route::get('/products', [CatalogController::class, 'indexProducts']);
        Route::get('/products/{product}', [CatalogController::class, 'showProduct']);
        Route::post('/products', [CatalogController::class, 'storeProduct']);
        Route::put('/products/{product}', [CatalogController::class, 'updateProduct']);
        Route::delete('/products/{product}', [CatalogController::class, 'destroyProduct']);

        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('/transactions', StoreTransactionAction::class);
        Route::post('/transactions/validate', ValidateTransactionAction::class);

        Route::get('/commodities', [CommodityController::class, 'index']);

        Route::get('/repayments', [RepaymentController::class, 'index']);
        Route::get('/repayments/{repayment}', [RepaymentController::class, 'show']);
        Route::post('/repayments', StoreRepaymentAction::class);

        Route::get('/settings', [SettingController::class, 'index']);
        Route::patch('/settings', [SettingController::class, 'update']);
    });
});
