<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/hello', function () {
    return response()->json(['message' => 'Hello World!']);
});

Route::post('/accounts', [AccountController::class, 'create']);

Route::post('/accounts/{accountNumber}/deposit', [TransactionController::class, 'deposit']);

Route::post('/accounts/{accountNumber}/withdraw', [TransactionController::class, 'withdraw']);

Route::get('/accounts/{accountNumber}/balance', [TransactionController::class, 'balance']);

