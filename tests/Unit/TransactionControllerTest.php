<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Transaction;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testDeposit()
    {
        $account = Account::create(['account_number' => 1]);

        $response = $this->postJson('/api/accounts/1/deposit', [
            'amount' => 100.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Depósito realizado com sucesso']);

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => 100.0,
            'original_amount' => 100.0,
            'currency' => 'USD',
            'type' => 'deposit'
        ]);
    }

    public function testWithdraw()
    {
        $account = Account::create(['account_number' => 1]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);

        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response([
                'value' => [
                    [
                        'cotacaoCompra' => 5.0,
                        'cotacaoVenda' => 5.5,
                        'dataHoraCotacao' => '2024-06-21 13:05:36.446',
                        'tipoBoletim' => 'Fechamento'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/accounts/1/withdraw', [
            'amount' => 50.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Saque realizado com sucesso']);

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 50.0
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => -50.0,
            'original_amount' => 50.0,
            'currency' => 'USD',
            'type' => 'withdrawal'
        ]);
    }

    public function testWithdrawWithConversion()
    {
        $account = Account::create(['account_number' => 1]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'BRL',
            'balance' => 500.0
        ]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);

        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response([
                'value' => [
                    [
                        'cotacaoCompra' => 5.0,
                        'cotacaoVenda' => 5.5,
                        'dataHoraCotacao' => '2024-06-21 13:05:36.446',
                        'tipoBoletim' => 'Fechamento'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/accounts/1/withdraw', [
            'amount' => 150.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Saque realizado com sucesso']);

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 0.0
        ]);

        $this->assertDatabaseHas('account_balances', [
            'account_id' => $account->id,
            'currency' => 'BRL',
            'balance' => 225.0
        ]);

        $this->assertDatabaseHas('transactions', [
            'account_id' => $account->id,
            'amount' => -150.0,
            'original_amount' => 150.0,
            'currency' => 'USD',
            'type' => 'withdrawal'
        ]);
    }

    public function testBalance()
    {
        $account = Account::create(['account_number' => 1]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 100.0
        ]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'BRL',
            'balance' => 500.0
        ]);

        Http::fake([
            'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/*' => Http::response([
                'value' => [
                    [
                        'cotacaoCompra' => 5.0,
                        'cotacaoVenda' => 5.5,
                        'dataHoraCotacao' => '2024-06-21 13:05:36.446',
                        'tipoBoletim' => 'Fechamento'
                    ]
                ]
            ], 200)
        ]);

        $response = $this->getJson('/api/accounts/1/balance?currency=USD');
        $response->assertStatus(200)
            ->assertJson([
                'account_number' => 1,
                'currency' => 'USD',
                'balance' => 100.0
            ]);

        $response = $this->getJson('/api/accounts/1/balance');
        $response->assertStatus(200);

        $responseData = $response->json();
        $expectedData = [
            'account_number' => 1,
            'balances' => [
                ['currency' => 'USD', 'balance' => 100.0],
                ['currency' => 'BRL', 'balance' => 500.0]
            ]
        ];

        $this->assertEquals($expectedData['account_number'], $responseData['account_number']);
        $this->assertEqualsCanonicalizing($expectedData['balances'], $responseData['balances']);
    }

    public function testDepositWithInvalidAccount()
    {
        $response = $this->postJson('/api/accounts/999/deposit', [
            'amount' => 100.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Conta não encontrada']);
    }

    public function testWithdrawWithInvalidAccount()
    {
        $response = $this->postJson('/api/accounts/999/withdraw', [
            'amount' => 100.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Conta não encontrada']);
    }

    public function testWithdrawWithInsufficientBalance()
    {
        $account = Account::create(['account_number' => 1]);
        AccountBalance::create([
            'account_id' => $account->id,
            'currency' => 'USD',
            'balance' => 50.0
        ]);

        $response = $this->postJson('/api/accounts/1/withdraw', [
            'amount' => 100.0,
            'currency' => 'USD'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Saldo insuficiente em todas as moedas']);
    }
}
