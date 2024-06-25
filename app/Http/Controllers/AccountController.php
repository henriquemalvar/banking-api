<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        $accountNumber = $this->generateAccountNumber();

        $account = Account::create([
            'account_number' => $accountNumber,
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account
        ], 201);
    }

    protected function generateAccountNumber()
    {
        $lastAccount = Account::orderBy('account_number', 'desc')->first();
        return $lastAccount ? $lastAccount->account_number + 1 : 1;
    }
}
