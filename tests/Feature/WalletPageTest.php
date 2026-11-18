<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class WalletPageTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    protected function createSantri(string $nama, Kelas $kelas, float $balance = 0): Santri
    {
        $santri = Santri::factory()->create([
            'nama' => $nama,
            'kelas_id' => $kelas->id,
            'branch_id' => $kelas->branch_id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => $balance,
            'daily_spending_limit' => 10000,
        ]);

        return $santri;
    }

    public function test_wallet_index_shows_kelas_cards_with_totals(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelasA = $this->newKelas($school, '7C');
        $kelasB = $this->newKelas($school, '8B');

        $this->createSantri('Santri Satu Tujuh C', $kelasA, 50000);
        $this->createSantri('Santri Dua Tujuh C', $kelasA, 30000);
        $this->createSantri('Santri Delapan B', $kelasB, 70000);

        $response = $this->get(route('wallet.index'));

        $response->assertStatus(200);
        $response->assertSee('7C');
        $response->assertSee('8B');
        $response->assertSee('Rp 150.000,00');
        $response->assertSee('Lihat Tabungan');
    }

    public function test_wallet_index_filters_santri_by_kelas(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelasA = $this->newKelas($school, '7C');
        $kelasB = $this->newKelas($school, '8B');

        $this->createSantri('Santri Hanya Tujuh C', $kelasA, 50000);
        $this->createSantri('Santri Hanya Delapan B', $kelasB, 70000);

        $response = $this->get(route('wallet.index', ['kelas_id' => $kelasA->id]));

        $response->assertStatus(200);
        $response->assertSee('Santri Hanya Tujuh C');
        $response->assertSee('Rp 50.000,00');
        $response->assertDontSee('Santri Hanya Delapan B');
    }

    public function test_wallet_kelas_detail_shows_correct_class_total_saldo(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas = $this->newKelas($school, '7C');

        $this->createSantri('Santri Satu', $kelas, 50000);
        $this->createSantri('Santri Dua', $kelas, 30000);

        $response = $this->get(route('wallet.index', ['kelas_id' => $kelas->id]));

        $response->assertStatus(200);
        $response->assertSee('Total Saldo');
        $response->assertSee('Rp 80.000,00');
    }

    public function test_wallet_index_searches_santri_across_kelas(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelasA = $this->newKelas($school, '7C');
        $kelasB = $this->newKelas($school, '8B');

        $this->createSantri('Rara Salsabila', $kelasA, 25000);
        $this->createSantri('Budi Santoso', $kelasB, 40000);

        $response = $this->get(route('wallet.index', ['search' => 'Rara']));

        $response->assertStatus(200);
        $response->assertSee('Rara Salsabila');
        $response->assertDontSee('Budi Santoso');
    }

    public function test_school_admin_cannot_access_other_school_data(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');
        $this->actingAsSchoolAdmin($schoolA);

        $kelasA = $this->newKelas($schoolA, '7A');
        $kelasB = $this->newKelas($schoolB, '7B');
        $santriB = $this->createSantri('Santri Sekolah B', $kelasB, 90000);

        $this->get(route('santri.edit', $santriB->id))->assertNotFound();

        $this->get(route('wallet.index'))
            ->assertOk()
            ->assertSee('7A')
            ->assertDontSee('7B')
            ->assertDontSee('Rp 90.000,00');
    }

    public function test_debit_penarikan_melebihi_saldo_ditangani_dengan_redirect(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas = $this->newKelas($school, '7C');
        $santri = $this->createSantri('Santri Tarik', $kelas, 10000);

        $this->from(route('wallet.editTransaction', $santri))
            ->post(route('wallet.processTransaction', $santri), [
                'type' => 'debit',
                'amount' => 20000,
                'description' => 'Penarikan melebihi saldo',
            ])
            ->assertRedirect(route('wallet.editTransaction', $santri))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $santri->id,
            'balance' => 10000,
        ]);

        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_debit_penarikan_melebihi_batas_harian_ditangani_dengan_redirect(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas = $this->newKelas($school, '7C');
        $santri = $this->createSantri('Santri Limit', $kelas, 50000);

        WalletTransaction::create([
            'wallet_id' => $santri->wallet->id,
            'amount' => 8000,
            'type' => 'debit',
            'description' => 'Beli tadi pagi',
            'reference_id' => null,
        ]);

        $this->from(route('wallet.editTransaction', $santri))
            ->post(route('wallet.processTransaction', $santri), [
                'type' => 'debit',
                'amount' => 3000,
                'description' => 'Melebihi batas harian',
            ])
            ->assertRedirect(route('wallet.editTransaction', $santri))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $santri->id,
            'balance' => 50000,
        ]);
    }

    public function test_debit_penarikan_mencatat_jurnal_titipan_ke_kas(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas = $this->newKelas($school, '7C');
        $santri = $this->createSantri('Santri Tarik Jurnal', $kelas, 50000);

        $this->from(route('wallet.editTransaction', $santri))
            ->post(route('wallet.processTransaction', $santri), [
                'type' => 'debit',
                'amount' => 5000,
                'description' => 'Penarikan uang saku',
            ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $santri->id,
            'balance' => 45000,
        ]);

        $liability = Account::where('account_code', AccountCodes::WALLET_LIABILITY)->value('id');
        $cash = Account::where('account_code', AccountCodes::CASH)->value('id');

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $liability,
            'debit' => 5000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $cash,
            'credit' => 5000,
        ]);
    }
}
