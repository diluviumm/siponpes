<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\SppTariff;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SppPaymentWithoutTariffTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function context(string $name): array
    {
        $school = $this->createSchool($name);
        $kelas = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 30,
            'branch_id' => $school->id,
        ]);
        $santri = Santri::factory()->create([
            'nama' => 'Santri SPP',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        return [$school, $kelas, $santri];
    }

    public function test_payment_page_opens_without_tariff_and_shows_notice(): void
    {
        [$school, $kelas] = $this->context('Sekolah Tanpa Tarif');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('invoices.spp-payment', ['kelas_id' => $kelas->id]))
            ->assertOk()
            ->assertSee('Belum tersedia Tarif SPP')
            ->assertSee('Silakan tambahkan Tarif SPP');
    }

    public function test_payment_page_works_when_tariff_exists(): void
    {
        [$school, $kelas, $santri] = $this->context('Sekolah Dengan Tarif');

        SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 200000,
            'daily_spending_limit' => 0,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('invoices.spp-payment', ['kelas_id' => $kelas->id]))
            ->assertOk()
            ->assertSee('Santri SPP')
            ->assertSee('Rp 100.000');
    }

    public function test_payment_without_tariff_is_rejected(): void
    {
        [$school, , $santri] = $this->context('Sekolah Tolak Tarif');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('invoices.spp-pay'), [
            'santri_id' => $santri->id,
            'amount' => 100000,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_payment_uses_server_tariff_amount_instead_of_request_amount(): void
    {
        [$school, , $santri] = $this->context('Sekolah Tarif Server');

        SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 200000,
            'daily_spending_limit' => 0,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('invoices.spp-pay'), [
            'santri_id' => $santri->id,
            'amount' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'santri_id' => $santri->id,
            'total_amount' => 100000,
            'status' => Invoice::STATUS_PAID,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 100000,
        ]);
    }
}
