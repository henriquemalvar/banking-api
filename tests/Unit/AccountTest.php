<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Transaction;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function testAccountHasBalances()
    {
        $account = Account::create(['account_number' => 1]);
        $balance = AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);

        $this->assertTrue($account->balances->contains($balance));
    }

    public function testAccountHasTransactions()
    {
        $account = Account::create(['account_number' => 1]);
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => 100.0,
            'original_amount' => 100.0,
            'currency' => 'USD',
            'type' => 'deposit'
        ]);

        $this->assertTrue($account->transactions->contains($transaction));
    }
}
