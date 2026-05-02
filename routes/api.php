<?php

use App\Features\Auth\Actions\LoginAction;
use App\Features\Auth\Actions\LogoutAction;
use App\Features\Catalog\Actions\CategoryProductsAction;
use App\Features\Catalog\Actions\DestroyCategoryAction;
use App\Features\Catalog\Actions\DestroyProductAction;
use App\Features\Catalog\Actions\IndexCategoryAction;
use App\Features\Catalog\Actions\IndexProductAction;
use App\Features\Catalog\Actions\ShowCategoryAction;
use App\Features\Catalog\Actions\ShowProductAction;
use App\Features\Catalog\Actions\StoreCategoryAction;
use App\Features\Catalog\Actions\StoreProductAction;
use App\Features\Catalog\Actions\UpdateCategoryAction;
use App\Features\Catalog\Actions\UpdateProductAction;
use App\Features\Farmers\Actions\DestroyFarmerAction;
use App\Features\Farmers\Actions\FarmerDebtsAction;
use App\Features\Farmers\Actions\FarmerTransactionsAction;
use App\Features\Farmers\Actions\IndexFarmerAction;
use App\Features\Farmers\Actions\ShowFarmerAction;
use App\Features\Farmers\Actions\StoreFarmerAction;
use App\Features\Farmers\Actions\UpdateFarmerAction;
use App\Features\Repayments\Actions\IndexRepaymentAction;
use App\Features\Repayments\Actions\ShowRepaymentAction;
use App\Features\Repayments\Actions\StoreRepaymentAction;
use App\Features\Transactions\Actions\IndexTransactionAction;
use App\Features\Transactions\Actions\ShowTransactionAction;
use App\Features\Transactions\Actions\StoreTransactionAction;
use App\Features\Users\Actions\DestroyUserAction;
use App\Features\Users\Actions\IndexUserAction;
use App\Features\Users\Actions\ShowUserAction;
use App\Features\Users\Actions\StoreUserAction;
use App\Features\Users\Actions\UpdateUserAction;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', LoginAction::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', LogoutAction::class);

        Route::get('/users', IndexUserAction::class);
        Route::get('/users/{user}', ShowUserAction::class);
        Route::post('/users', StoreUserAction::class);
        Route::put('/users/{user}', UpdateUserAction::class);
        Route::delete('/users/{user}', DestroyUserAction::class);

        Route::get('/farmers', IndexFarmerAction::class);
        Route::get('/farmers/{farmer}', ShowFarmerAction::class);
        Route::post('/farmers', StoreFarmerAction::class);
        Route::put('/farmers/{farmer}', UpdateFarmerAction::class);
        Route::delete('/farmers/{farmer}', DestroyFarmerAction::class);
        Route::get('/farmers/{farmer}/debts', FarmerDebtsAction::class);
        Route::get('/farmers/{farmer}/transactions', FarmerTransactionsAction::class);

        Route::get('/categories', IndexCategoryAction::class);
        Route::get('/categories/{category}', ShowCategoryAction::class);
        Route::get('/categories/{category}/products', CategoryProductsAction::class);
        Route::post('/categories', StoreCategoryAction::class);
        Route::put('/categories/{category}', UpdateCategoryAction::class);
        Route::delete('/categories/{category}', DestroyCategoryAction::class);

        Route::get('/products', IndexProductAction::class);
        Route::get('/products/{product}', ShowProductAction::class);
        Route::post('/products', StoreProductAction::class);
        Route::put('/products/{product}', UpdateProductAction::class);
        Route::delete('/products/{product}', DestroyProductAction::class);

        Route::get('/transactions', IndexTransactionAction::class);
        Route::get('/transactions/{transaction}', ShowTransactionAction::class);
        Route::post('/transactions', StoreTransactionAction::class);

        Route::get('/repayments', IndexRepaymentAction::class);
        Route::get('/repayments/{repayment}', ShowRepaymentAction::class);
        Route::post('/repayments', StoreRepaymentAction::class);
    });
});
