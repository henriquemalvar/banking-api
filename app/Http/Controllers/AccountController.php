<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        // Exemplo de geração de número de conta. Adapte conforme necessário.
        $accountNumber = $this->generateAccountNumber();

        $account = Account::create([
            'account_number' => $accountNumber,
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'account' => $account
        ], 201);
    }

    // Exemplo de método para gerar um número de conta. Implemente sua lógica aqui.
    protected function generateAccountNumber()
    {
        // Supondo que você queira um número sequencial, você pode buscar o último número e incrementá-lo.
        // Isso é apenas um exemplo. Ajuste conforme a lógica necessária para seu caso.
        $lastAccount = Account::orderBy('account_number', 'desc')->first();
        return $lastAccount ? $lastAccount->account_number + 1 : 1;
    }
}
