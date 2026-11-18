<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Santri;
use App\Models\Wallet;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class JournalAdjustingTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_adjusting_journal_is_created_when_balanced(): void
    {
        $school = $this->createSchool('Sekolah Jurnal');
        $this->actingAsSchoolAdmin($school);

        $cash = Account::where('account_code', AccountCodes::CASH)->value('id');
        $revenue = Account::where('account_code', AccountCodes::OTHER_REVENUE)->value('id');

        $this->post(route('financial.adjusting.store'), [
            'branch_id' => $school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Koreksi pendapatan lain',
            'entries' => [
                ['account_id' => $cash, 'debit' => 10000, 'credit' => null],
                ['account_id' => $revenue, 'debit' => null, 'credit' => 10000],
            ],
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('journals', [
            'description' => 'Koreksi pendapatan lain',
            'branch_id' => $school->id,
        ]);
    }

    public function test_unbalanced_adjusting_journal_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Jurnal B');
        $this->actingAsSchoolAdmin($school);

        $cash = Account::where('account_code', AccountCodes::CASH)->value('id');
        $revenue = Account::where('account_code', AccountCodes::OTHER_REVENUE)->value('id');

        $this->post(route('financial.adjusting.store'), [
            'branch_id' => $school->id,
            'transaction_date' => now()->toDateString(),
            'description' => 'Tidak seimbang',
            'entries' => [
                ['account_id' => $cash, 'debit' => 10000, 'credit' => null],
                ['account_id' => $revenue, 'debit' => null, 'credit' => 9000],
            ],
        ])->assertSessionHasErrors('entries');

        $this->assertDatabaseMissing('journals', [
            'description' => 'Tidak seimbang',
        ]);
    }

    public function test_close_book_moves_surplus_to_retained_earnings(): void
    {
        $school = $this->createSchool('Sekolah Tutup Buku');
        $kelas = $this->newKelas($school, '7K');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Tutup Buku',
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
            'Pembayaran SPP santri',
            'INV-9999',
            [
                ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => 50000, 'credit' => 0],
                ['account_code' => AccountCodes::SPP_REVENUE, 'debit' => 0, 'credit' => 50000],
            ],
            now()->toDateString(),
            $school->id
        );

        $year = now()->year;

        $this->post(route('financial.close'), [
            'branch_id' => $school->id,
            'year' => $year,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('journals', [
            'reference' => "CLOSING-{$year}",
            'branch_id' => $school->id,
        ]);

        $this->post(route('financial.close'), [
            'branch_id' => $school->id,
            'year' => $year,
        ])->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_close_book_handles_net_debit_revenue_account(): void
    {
        $school = $this->createSchool('Sekolah Tutup Defisit');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', AccountCodes::OTHER_REVENUE)->value('id');
        $retained = Account::where('account_code', AccountCodes::RETAINED_EARNINGS)->value('id');

        app(JournalService::class)->createJournalEntry(
            'Koreksi pengembalian',
            'ADJ-DEFISIT',
            [
                ['account_code' => AccountCodes::OTHER_REVENUE, 'debit' => 10000, 'credit' => 0],
                ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => 10000],
            ],
            now()->toDateString(),
            $school->id
        );

        $year = now()->year;

        $this->post(route('financial.close'), [
            'branch_id' => $school->id,
            'year' => $year,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $journal = Journal::where('reference', "CLOSING-{$year}")
            ->where('branch_id', $school->id)
            ->firstOrFail();

        $details = $journal->details;

        $this->assertTrue($details->every(fn ($d) => $d->debit >= 0 && $d->credit >= 0));
        $this->assertEqualsWithDelta($details->sum('debit'), $details->sum('credit'), 0.01);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $journal->id,
            'account_id' => $revenue,
            'credit' => 10000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $journal->id,
            'account_id' => $retained,
            'debit' => 10000,
        ]);
    }
}
