<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenTransactionTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_canteen_transaction_succeeds_when_balance_and_limit_are_valid(): void
    {
        $school = $this->createSchool('Sekolah Kantin A');
        $kelas = $this->newKelas($school, 'X IPA');

        $santri = Santri::factory()->create([
            'santri_card_token' => 'card-token-123',
            'email' => 'wali@example.com',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 10000,
            'type' => 'debit',
            'description' => 'Existing purchase',
            'reference_id' => null,
        ]);

        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'card-token-123',
            'amount' => 20000,
            'canteen_name' => 'Canteen A',
        ], [
            'X-API-KEY' => $school->api_key,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Transaction completed successfully',
            'data' => [
                'santri_id' => $santri->id,
                'amount' => 20000,
                'canteen_name' => 'Canteen A',
            ],
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'amount' => 20000,
            'type' => 'debit',
            'description' => 'Canteen purchase at Canteen A',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 80000,
        ]);
    }

    public function test_canteen_transaction_fails_when_daily_limit_exceeded(): void
    {
        $school = $this->createSchool('Sekolah Kantin B');
        $kelas = $this->newKelas($school, 'XI IPS');

        $santri = Santri::factory()->create([
            'santri_card_token' => 'card-token-456',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 20000,
        ]);

        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => 15000,
            'type' => 'debit',
            'description' => 'Existing purchase',
            'reference_id' => null,
        ]);

        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'card-token-456',
            'amount' => 10000,
            'canteen_name' => 'Canteen A',
        ], [
            'X-API-KEY' => $school->api_key,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Daily spending limit exceeded']);
    }

    public function test_canteen_transaction_rejects_invalid_api_key(): void
    {
        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'any-token',
            'amount' => 10000,
            'canteen_name' => 'Canteen A',
        ], [
            'X-API-KEY' => 'invalid-key',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthorized']);
    }
}
