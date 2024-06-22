<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'currency' => 'sometimes|string|size:3',
        ]);

        $accountNumber = $this->generateAccountNumber();

        $currency = $request->input('currency', 'BRL');

        $account = Account::create([
            'account_number' => $accountNumber,
            'balance' => 0,
            'currency' => $currency,
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account
        ], 201);
    }

    private function generateAccountNumber()
    {
        do {
            $accountNumber = random_int(1000000000, 9999999999);
        } while (Account::where('account_number', $accountNumber)->exists());

        return $accountNumber;
    }
}
