<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class FinancialReportControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_general_journal_page_displays_journal_entries(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Pembayaran SPP santri',
            'INV-1001',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ]
        );

        $response = $this->get(route('financial.journal'));

        $response->assertStatus(200);
        $response->assertSee('Jurnal Umum');
        $response->assertSee('INV-1001');
        $response->assertSee('1010 - Kas/Bank');
        $response->assertSee('4020 - Pendapatan SPP');
    }

    public function test_ledger_page_displays_account_entries_and_balance(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Top up wallet',
            'WALLET-1',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '2010', 'debit' => 0, 'credit' => 50000],
            ]
        );

        $account = Account::where('account_code', '1010')->first();

        $response = $this->get(route('financial.ledger', ['account_id' => $account->id]));

        $response->assertStatus(200);
        $response->assertSee('Buku Besar');
        $response->assertSee('Kas/Bank');
        $response->assertSee('Rp 50.000,00');
        $response->assertSee('Saldo Akun');
    }

    public function test_trial_balance_page_displays_debit_and_credit_totals(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Top up wallet',
            'WALLET-1',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '2010', 'debit' => 0, 'credit' => 50000],
            ]
        );

        $response = $this->get(route('financial.trial_balance'));

        $response->assertStatus(200);
        $response->assertSee('Neraca Saldo');
        $response->assertSee('Kas/Bank');
        $response->assertSee('Titipan Santri / Wallet Liabilities');
        $response->assertSee('Rp 50.000,00');
    }

    public function test_balance_sheet_page_displays_asset_and_liability_totals(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Top up wallet',
            'WALLET-1',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '2010', 'debit' => 0, 'credit' => 50000],
            ]
        );

        $response = $this->get(route('financial.balance_sheet'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Neraca');
        $response->assertSee('Aset');
        $response->assertSee('Kewajiban & Ekuitas', false);
        $response->assertSee('Kas/Bank');
        $response->assertSee('Titipan Santri / Wallet Liabilities');
    }

    public function test_income_statement_page_displays_revenue_and_net_income(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Pembayaran SPP',
            'INV-1002',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ]
        );

        $response = $this->get(route('financial.income_statement'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Surplus/Defisit');
        $response->assertSee('Pendapatan');
        $response->assertSee('Rp 100.000,00');
        $response->assertSee('Surplus (Defisit)');
    }

    public function test_general_journal_can_be_exported_as_pdf(): void
    {
        $journalService = app(JournalService::class);
        $this->actingAsSuperadmin();

        $journalService->createJournalEntry(
            'Pembayaran SPP santri',
            'INV-1001',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ]
        );

        $response = $this->get(route('financial.journal', ['pdf' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_trial_balance_can_be_exported_as_pdf(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->get(route('financial.trial_balance', ['pdf' => 1]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_financial_report_rejects_nonexistent_school(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('financial.journal', ['school_id' => 999999]))
            ->assertNotFound();

        $this->get(route('financial.ledger', ['school_id' => 999999]))
            ->assertNotFound();

        $this->get(route('financial.balance_sheet', ['school_id' => 999999]))
            ->assertNotFound();
    }
}
