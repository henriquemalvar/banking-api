<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\AccountBalance;
use App\Services\ExchangeRateService;

class TransactionController extends Controller
{
    public function deposit(Request $request, $accountNumber)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3'
        ]);

        $account = Account::where('account_number', $accountNumber)->first();
        if (!$account) {
            return response()->json(['message' => 'Conta não encontrada'], 404);
        }

        $currency = $request->currency;
        $amount = $request->amount;

        $accountBalance = AccountBalance::firstOrCreate([
            'account_id' => $account->id,
            'currency' => $currency
        ]);

        $accountBalance->balance += $amount;
        $accountBalance->save();

        Transaction::create([
            'account_id' => $account->id,
            'amount' => $amount,
            'original_amount' => $amount,
            'currency' => $currency,
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

        $exchangeRateService = new ExchangeRateService();
        $currency = $request->currency;
        $amount = $request->amount;

        $accountBalance = AccountBalance::where('account_id', $account->id)
            ->where('currency', $currency)
            ->first();

        $totalBalanceInRequestedCurrency = $accountBalance ? $accountBalance->balance : 0;

        $accountBalances = AccountBalance::where('account_id', $account->id)->get();
        $totalBalanceInBRL = 0;

        foreach ($accountBalances as $balance) {
            if ($balance->currency == 'BRL') {
                $totalBalanceInBRL += $balance->balance;
            } else {
                try {
                    $exchangeRate = $exchangeRateService->getExchangeRate($balance->currency);
                    $totalBalanceInBRL += $balance->balance * $exchangeRate['buy'];
                } catch (\Exception $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
            }
        }

        try {
            $requestedExchangeRate = $exchangeRateService->getExchangeRate($currency);
            $requiredAmountInBRL = $amount * $requestedExchangeRate['sell'];
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        if ($totalBalanceInBRL < $requiredAmountInBRL) {
            return response()->json(['message' => 'Saldo insuficiente em todas as moedas'], 400);
        }

        if ($totalBalanceInRequestedCurrency >= $amount) {
            $accountBalance->balance -= $amount;
            $accountBalance->save();

            Transaction::create([
                'account_id' => $account->id,
                'amount' => -$amount,
                'original_amount' => $amount,
                'currency' => $currency,
                'type' => 'withdrawal'
            ]);

            return response()->json(['message' => 'Saque realizado com sucesso'], 201);
        } else {
            $requiredAmount = $amount - $totalBalanceInRequestedCurrency;
            $remainingRequiredAmountInBRL = $requiredAmount * $requestedExchangeRate['sell'];

            foreach ($accountBalances as $balance) {
                if ($remainingRequiredAmountInBRL <= 0) {
                    break;
                }

                if ($balance->currency == 'BRL') {
                    $deductedAmount = min($remainingRequiredAmountInBRL, $balance->balance);
                    $balance->balance -= $deductedAmount;
                    $remainingRequiredAmountInBRL -= $deductedAmount;
                } else {
                    try {
                        $exchangeRate = $exchangeRateService->getExchangeRate($balance->currency);
                        $amountToDeductInCurrency = min($remainingRequiredAmountInBRL / $exchangeRate['buy'], $balance->balance);
                        $balance->balance -= $amountToDeductInCurrency;
                        $remainingRequiredAmountInBRL -= $amountToDeductInCurrency * $exchangeRate['buy'];
                    } catch (\Exception $e) {
                        return response()->json(['message' => $e->getMessage()], 500);
                    }
                }

                $balance->save();
            }

            if ($accountBalance) {
                $accountBalance->balance = 0;
            } else {
                $accountBalance = new AccountBalance([
                    'account_id' => $account->id,
                    'currency' => $currency,
                    'balance' => 0
                ]);
            }
            $accountBalance->save();

            Transaction::create([
                'account_id' => $account->id,
                'amount' => -$amount,
                'original_amount' => $amount,
                'currency' => $currency,
                'type' => 'withdrawal'
            ]);

            return response()->json(['message' => 'Saque realizado com sucesso'], 201);
        }
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
        $exchangeRateService = new ExchangeRateService();
        $currency = $request->currency;
        if ($currency) {
            $accountBalance = AccountBalance::where('account_id', $account->id)
                ->where('currency', $currency)
                ->first();
            if (!$accountBalance) {
                return response()->json(['message' => 'Saldo não encontrado para a moeda especificada'], 404);
            }
            return response()->json([
                'account_number' => $account->account_number,
                'currency' => $currency,
                'balance' => $accountBalance->balance
            ], 200);
        } else {
            $balances = AccountBalance::where('account_id', $account->id)->get();
            $sortedBalances = $balances->sortBy('currency')->values();
            $balancesArray = $sortedBalances->map(function ($balance) {
                return [
                    'currency' => $balance->currency,
                    'balance' => $balance->balance
                ];
            });
            return response()->json([
                'account_number' => $account->account_number,
                'balances' => $balancesArray
            ], 200);
        }
    }
}
