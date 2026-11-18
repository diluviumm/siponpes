<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\Wallet;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CashFlowTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_cash_flow_page_lists_cash_movements(): void
    {
        $school = $this->createSchool('Sekolah Arus Kas');
        $kelas = $this->newKelas($school, '7L');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Arus Kas',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $this->actingAsSchoolAdmin($school);

        app(JournalService::class)->createJournalEntry(
            'Top up wallet untuk santri',
            'WALLET-100',
            [
                ['account_code' => AccountCodes::CASH, 'debit' => 75000, 'credit' => 0],
                ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => 0, 'credit' => 75000],
            ],
            now()->toDateString(),
            $school->id
        );

        $this->get(route('financial.cash_flow'))
            ->assertOk()
            ->assertSee('Laporan Arus Kas')
            ->assertSee('Top up wallet untuk santri')
            ->assertSee('Rp 75.000,00');
    }

    public function test_cash_flow_can_be_exported_as_pdf(): void
    {
        $school = $this->createSchool('Sekolah Arus Kas PDF');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('financial.cash_flow', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
