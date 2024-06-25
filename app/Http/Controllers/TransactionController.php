<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function deposit(Request $request, $accountNumber)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'required|string|size:3'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        }

        $account = Account::where('account_number', $accountNumber)->first();
        if (!$account) {
            return response()->json(['message' => 'Conta não encontrada'], 404);
        }

        // Verifica se a moeda do depósito é a mesma da conta
        if ($request->currency !== $account->currency) {
            return response()->json(['message' => 'A moeda do depósito não corresponde à moeda da conta'], 422);
        }

        // Atualiza o saldo da conta
        $account->balance += $request->amount;
        $account->save();

        // Cria a transação de depósito
        Transaction::create([
            'account_id' => $account->id,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'type' => 'deposit'
        ]);

        return response()->json(['message' => 'Depósito realizado com sucesso'], 201);
    }

    public function withdraw(Request $request, $accountNumber)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3'
        ]);

        $account = Account::where('account_number', $accountNumber)->first();
        if (!$account) {
            return response()->json(['message' => 'Conta não encontrada'], 404);
        }

        $amountRequested = $request->amount;
        $currencyRequested = $request->currency;

        // Verificar se a moeda solicitada é a mesma da conta
        if ($currencyRequested !== $account->currency) {
            return response()->json(['message' => 'Operação permitida apenas na mesma moeda da conta'], 400);
        }

        // Verificar se o saldo é suficiente para o saque
        if ($account->balance < $amountRequested) {
            return response()->json(['message' => 'Saldo insuficiente'], 400);
        }

        Transaction::create([
            'account_id' => $account->id,
            'amount' => -$amountRequested,
            'currency' => $currencyRequested,
            'type' => 'withdrawal'
        ]);

        // Atualizar o saldo da conta
        $account->balance -= $amountRequested;
        $account->save();

        return response()->json(['message' => 'Saque realizado com sucesso'], 201);
    }


    public function balance(Request $request, $accountNumber)
    {
        $request->validate([
            'currency' => 'nullable|string|size:3'
        ]);

        $account = Account::where('account_number', $accountNumber)->first();
        if (!$account) {
            return response()->json(['message' => 'Conta não encontrada'], 404);
        }

        if ($request->has('currency') && $request->currency !== $account->currency) {
            $exchangeRateService = new ExchangeRateService();
            $targetCurrency = $request->currency;
            $amountInBRL = $exchangeRateService->convertToBRL($account->balance, $account->currency);
            $totalBalanceInTargetCurrency = $exchangeRateService->convertFromBRL($amountInBRL, $targetCurrency);

            return response()->json([
                'account_number' => $account->account_number,
                'currency' => $targetCurrency,
                'balance' => $totalBalanceInTargetCurrency
            ], 200);
        }

        return response()->json(
            [
                'account_number' => $account->account_number,
                'currency' => $account->currency,
                'balance' => $account->balance
            ],
            200
        );
    }
}
