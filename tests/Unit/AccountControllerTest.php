<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateAccount()
    {
        // Simula uma requisição para criar uma nova conta
        $response = $this->postJson('/api/accounts');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'account' => [
                    'id',
                    'account_number',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('accounts', [
            'account_number' => 1
        ]);

        // Cria mais uma conta e verifica se o número da conta é incrementado
        $response = $this->postJson('/api/accounts');

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'account' => [
                    'id',
                    'account_number',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('accounts', [
            'account_number' => 2
        ]);
    }
}
