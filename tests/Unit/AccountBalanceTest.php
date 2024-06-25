<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\AccountBalance;

class AccountBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function testAccountBalanceBelongsToAccount()
    {
        $account = Account::create(['account_number' => 1]);
        $balance = AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);

        $this->assertEquals($account->id, $balance->account->id);
    }
}
