<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello World!']);
});

Route::prefix('/accounts')->group(function () {
    Route::post('/', [AccountController::class, 'create']);

    Route::post('/{accountNumber}/deposit', [TransactionController::class, 'deposit']);

    Route::post('/{accountNumber}/withdraw', [TransactionController::class, 'withdraw']);

    Route::get('/{accountNumber}/balance', [TransactionController::class, 'balance']);
});


Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
