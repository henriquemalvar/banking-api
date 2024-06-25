<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\Transaction;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function testTransactionBelongsToAccount()
    {
        $account = Account::create(['account_number' => 1]);
        $transaction = Transaction::create([
            'account_id' => $account->id,
            'amount' => 100.0,
            'original_amount' => 100.0,
            'currency' => 'USD',
            'type' => 'deposit'
        ]);

        $this->assertEquals($account->id, $transaction->account->id);
    }
}
