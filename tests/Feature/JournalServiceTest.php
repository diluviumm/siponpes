<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Wallet;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_journal_service_creates_journal_and_details(): void
    {
        $journalService = app(JournalService::class);

        $journal = $journalService->createJournalEntry(
            'Uji jurnal',
            'TEST-001',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ]
        );

        $this->assertDatabaseHas('journals', [
            'id' => $journal->id,
            'description' => 'Uji jurnal',
            'reference' => 'TEST-001',
        ]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $journal->id,
            'debit' => 100000,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $journal->id,
            'debit' => 0,
            'credit' => 100000,
        ]);
    }

    public function test_wallet_top_up_creates_top_up_journal(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'X IPA',
            'wali_kelas' => 'Pak Budi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali@example.com',
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 0,
            'daily_spending_limit' => 50000,
        ]);

        $response = $this->post(route('wallet.processTransaction', $santri), [
            'amount' => 150000,
            'type' => 'credit',
            'description' => 'Top up saldo',
        ]);

        $response->assertRedirect(route('wallet.index'));

        $this->assertDatabaseHas('journal_details', [
            'debit' => 150000,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 0,
            'credit' => 150000,
        ]);
    }

    public function test_invoice_payment_creates_spp_revenue_journal(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI IPS',
            'wali_kelas' => 'Bu Santi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali2@example.com',
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 200000,
            'daily_spending_limit' => 50000,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-TEST-500',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->post(route('invoices.pay', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));

        $this->assertDatabaseHas('journals', [
            'description' => "Pembayaran SPP untuk invoice {$invoice->invoice_number}",
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 120000,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 0,
            'credit' => 120000,
        ]);

        $walletLiability = Account::where('account_code', AccountCodes::WALLET_LIABILITY)->value('id');
        $sppRevenue = Account::where('account_code', AccountCodes::SPP_REVENUE)->value('id');

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $walletLiability,
            'debit' => 120000,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $sppRevenue,
            'debit' => 0,
            'credit' => 120000,
        ]);
    }
}
