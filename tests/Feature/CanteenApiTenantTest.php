<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenApiTenantTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_school_cannot_debit_wallet_of_santri_from_another_school(): void
    {
        $schoolA = $this->createSchool('Sekolah Kantin A');
        $schoolB = $this->createSchool('Sekolah Kantin B');

        $kelasA = $this->newKelas($schoolA, 'X A');
        $kelasB = $this->newKelas($schoolB, 'X B');

        $santriA = Santri::factory()->create([
            'santri_card_token' => 'token-A',
            'kelas_id' => $kelasA->id,
            'branch_id' => $schoolA->id,
        ]);

        $santriB = Santri::factory()->create([
            'santri_card_token' => 'token-B',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);

        $walletB = Wallet::create([
            'santri_id' => $santriB->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'token-B',
            'amount' => 10000,
            'canteen_name' => 'Kantin A',
        ], [
            'X-API-KEY' => $schoolA->api_key,
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Santri not found']);

        $this->assertDatabaseHas('wallets', [
            'id' => $walletB->id,
            'balance' => 100000,
        ]);

        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $walletB->id,
            'amount' => 10000,
        ]);
    }

    public function test_school_can_debit_wallet_of_own_santri(): void
    {
        $school = $this->createSchool('Sekolah Kantin A');
        $kelas = $this->newKelas($school, 'X A');

        $santri = Santri::factory()->create([
            'santri_card_token' => 'token-own',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'token-own',
            'amount' => 10000,
            'canteen_name' => 'Kantin A',
        ], [
            'X-API-KEY' => $school->api_key,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Transaction completed successfully',
            'data' => ['santri_id' => $santri->id],
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 90000,
        ]);
    }

    public function test_soft_deleted_santri_cannot_transact(): void
    {
        $school = $this->createSchool('Sekolah Kantin A');
        $kelas = $this->newKelas($school, 'X A');

        $santri = Santri::factory()->create([
            'santri_card_token' => 'token-deleted',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        $santri->delete();

        $response = $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'token-deleted',
            'amount' => 10000,
            'canteen_name' => 'Kantin A',
        ], [
            'X-API-KEY' => $school->api_key,
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Santri not found']);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 100000,
        ]);
    }
}
