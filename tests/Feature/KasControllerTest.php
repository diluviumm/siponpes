<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\KasTransaction;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class KasControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_school_admin_can_record_pemasukan(): void
    {
        $school = $this->createSchool('Sekolah Kas A');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 500000,
            'description' => 'Sumbangan gedung',
        ])->assertRedirect(route('kas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('kas_transactions', [
            'branch_id' => $school->id,
            'type' => 'pemasukan',
            'amount' => 500000,
        ]);

        $kas = KasTransaction::first();

        $this->assertNotNull($kas->journal_id);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $kas->journal_id,
            'account_id' => Account::where('account_code', '1010')->value('id'),
            'debit' => 500000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $kas->journal_id,
            'account_id' => $revenue->id,
            'credit' => 500000,
        ]);
    }

    public function test_school_admin_can_record_pengeluaran(): void
    {
        $school = $this->createSchool('Sekolah Kas B');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 500000,
            'description' => 'Saldo awal',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 250000,
            'description' => 'Tagihan listrik',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $kas = KasTransaction::where('type', 'pengeluaran')->first();

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $kas->journal_id,
            'account_id' => $expense->id,
            'debit' => 250000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $kas->journal_id,
            'account_id' => Account::where('account_code', '1010')->value('id'),
            'credit' => 250000,
        ]);
    }

    public function test_account_must_match_transaction_type(): void
    {
        $school = $this->createSchool('Sekolah Kas C');
        $this->actingAsSchoolAdmin($school);

        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 100000,
            'description' => 'Salah akun',
        ])->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('kas_transactions', 0);
    }

    public function test_superadmin_requires_branch_id(): void
    {
        $this->actingAsSuperadmin();

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_school_admin_is_scoped_to_own_school(): void
    {
        $schoolA = $this->createSchool('Sekolah Kas A');
        $schoolB = $this->createSchool('Sekolah Kas B');

        $this->actingAsSchoolAdmin($schoolA);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
        ])->assertSessionHas('success');

        $this->actingAsSchoolAdmin($schoolB);

        $this->get(route('kas.index'))
            ->assertOk()
            ->assertDontSee('Pemasukan kas');
    }

    public function test_delete_kas_removes_its_journal(): void
    {
        $school = $this->createSchool('Sekolah Kas D');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5050')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 200000,
            'description' => 'Saldo awal',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 75000,
            'description' => 'Beban lain-lain',
        ])->assertSessionHas('success');

        $kas = KasTransaction::where('type', 'pengeluaran')->first();
        $journalId = $kas->journal_id;

        $this->delete(route('kas.destroy', $kas))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kas_transactions', ['id' => $kas->id]);
        $this->assertDatabaseMissing('journals', ['id' => $journalId]);
        $this->assertDatabaseMissing('journal_details', ['journal_id' => $journalId]);
    }

    public function test_school_admin_can_edit_kas_and_journal_is_recreated(): void
    {
        $school = $this->createSchool('Sekolah Kas Edit');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Sumbangan lama',
        ])->assertSessionHas('success');

        $kas = KasTransaction::first();
        $oldJournalId = $kas->journal_id;

        $this->get(route('kas.edit', $kas))
            ->assertOk()
            ->assertSee('Edit Transaksi Kas')
            ->assertSee('Sumbangan lama');

        $this->put(route('kas.update', $kas), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 175000,
            'description' => 'Sumbangan baru',
        ])->assertRedirect(route('kas.index'))
            ->assertSessionHas('success');

        $kas->refresh();

        $this->assertSame('175000.00', (string) $kas->amount);
        $this->assertSame('Sumbangan baru', $kas->description);
        $this->assertNotSame($oldJournalId, $kas->journal_id);

        $this->assertDatabaseMissing('journals', ['id' => $oldJournalId]);
        $this->assertDatabaseMissing('journal_details', ['journal_id' => $oldJournalId]);

        $this->assertDatabaseHas('journal_details', [
            'journal_id' => $kas->journal_id,
            'account_id' => Account::where('account_code', '1010')->value('id'),
            'debit' => 175000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => KasTransaction::class,
            'auditable_id' => $kas->id,
        ]);

        $audit = AuditLog::where('action', 'updated')
            ->where('auditable_type', KasTransaction::class)
            ->where('auditable_id', $kas->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertArrayHasKey('amount', $audit->changes ?? []);
        $this->assertArrayHasKey('description', $audit->changes ?? []);
    }

    public function test_audit_log_for_kas_stores_branch_id(): void
    {
        $school = $this->createSchool('Sekolah Kas Audit Branch');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 150000,
            'description' => 'Sumbangan teraudit',
        ])->assertSessionHas('success');

        $kas = KasTransaction::first();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => KasTransaction::class,
            'auditable_id' => $kas->id,
            'action' => 'created',
            'branch_id' => $school->id,
        ]);
    }

    public function test_kas_history_route_shows_deleted_transactions(): void
    {
        $school = $this->createSchool('Sekolah Kas Riwayat');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 250000,
            'description' => 'Sumbangan dihapus nanti',
        ])->assertSessionHas('success');

        $kas = KasTransaction::first();

        $this->delete(route('kas.destroy', $kas))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->get(route('kas.riwayat'))
            ->assertOk()
            ->assertSee('Dihapus')
            ->assertSee('Sumbangan dihapus nanti');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => KasTransaction::class,
            'auditable_id' => $kas->id,
            'action' => 'deleted',
            'branch_id' => $school->id,
        ]);
    }

    public function test_kas_history_is_scoped_per_school(): void
    {
        $schoolA = $this->createSchool('Sekolah Kas Riwayat A');
        $schoolB = $this->createSchool('Sekolah Kas Riwayat B');

        $this->actingAsSchoolAdmin($schoolA);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Riwayat sekolah A',
        ])->assertSessionHas('success');

        $this->actingAsSchoolAdmin($schoolB);

        $this->get(route('kas.riwayat'))
            ->assertOk()
            ->assertDontSee('Riwayat sekolah A');
    }

    public function test_kas_can_be_exported_as_pdf_and_excel(): void
    {
        $school = $this->createSchool('Sekolah Kas E');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 200000,
            'description' => 'Sumbangan',
        ])->assertSessionHas('success');

        $this->get(route('kas.index', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('kas.index', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_pengeluaran_is_rejected_when_balance_is_insufficient(): void
    {
        $school = $this->createSchool('Sekolah Kas Saldo');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Saldo awal',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 150000,
            'description' => 'Melebihi saldo',
        ])->assertSessionHasErrors([
            'amount' => 'Saldo kas tidak mencukupi. Saldo tersedia Rp100.000.',
        ]);

        $this->assertDatabaseMissing('kas_transactions', [
            'description' => 'Melebihi saldo',
        ]);
    }

    public function test_editing_pengeluaran_beyond_available_balance_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Kas Edit Saldo');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Saldo awal',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 50000,
            'description' => 'Listrik',
        ])->assertSessionHas('success');

        $kas = KasTransaction::where('type', 'pengeluaran')->firstOrFail();

        $this->put(route('kas.update', $kas), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 150000,
            'description' => 'Listrik naik',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('kas_transactions', [
            'id' => $kas->id,
            'amount' => 50000,
        ]);
    }

    public function test_saldo_kas_tersedia_is_cumulative_and_not_negative_after_income(): void
    {
        $school = $this->createSchool('Sekolah Kas Kumulatif');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 300000,
            'description' => 'Saldo bulan lalu',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 100000,
            'description' => 'Pengeluaran bulan ini',
        ])->assertSessionHas('success');

        $this->get(route('kas.index', ['from' => now()->startOfMonth()->toDateString()]))
            ->assertOk()
            ->assertSee('Saldo Kas Tersedia')
            ->assertSee('Rp 200.000,00');
    }

    public function test_delete_pemasukan_that_would_cause_negative_balance_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Kas Hapus Negatif');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Sumbangan A',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 60000,
            'description' => 'Listrik A',
        ])->assertSessionHas('success');

        $kasPemasukan = KasTransaction::where('type', 'pemasukan')->firstOrFail();

        // Deleting the income would leave only the 60k expense → balance running = -60k → should reject
        $this->delete(route('kas.destroy', $kasPemasukan))
            ->assertSessionHasErrors('id');

        $this->assertDatabaseHas('kas_transactions', ['id' => $kasPemasukan->id]);
    }

    public function test_delete_pemasukan_with_zero_balance_afterwards_succeeds(): void
    {
        $school = $this->createSchool('Sekolah Kas Hapus Aman');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Sumbangan B',
        ])->assertSessionHas('success');

        $kasPemasukan = KasTransaction::where('type', 'pemasukan')->firstOrFail();

        // Deleting the only income → balance = 0 (not < 0) → allowed
        $this->delete(route('kas.destroy', $kasPemasukan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kas_transactions', ['id' => $kasPemasukan->id]);
    }

    public function test_delete_pengeluaran_always_succeeds(): void
    {
        $school = $this->createSchool('Sekolah Kas Hapus Pengeluaran');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $this->post(route('kas.store'), [
            'type' => 'pemasukan',
            'transaction_date' => now()->toDateString(),
            'account_id' => $revenue->id,
            'amount' => 100000,
            'description' => 'Saldo awal',
        ])->assertSessionHas('success');

        $this->post(route('kas.store'), [
            'type' => 'pengeluaran',
            'transaction_date' => now()->toDateString(),
            'account_id' => $expense->id,
            'amount' => 50000,
            'description' => 'Belanja',
        ])->assertSessionHas('success');

        $kasPengeluaran = KasTransaction::where('type', 'pengeluaran')->firstOrFail();

        $this->delete(route('kas.destroy', $kasPengeluaran))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kas_transactions', ['id' => $kasPengeluaran->id]);
    }

    public function test_delete_pemasukan_is_rejected_when_historical_balance_would_go_negative(): void
    {
        $school = $this->createSchool('Sekolah Kas Histori');
        $this->actingAsSchoolAdmin($school);

        $revenue = Account::where('account_code', '4030')->firstOrFail();
        $expense = Account::where('account_code', '5010')->firstOrFail();

        $createdAt = now();

        // Create transactions directly (bypass controller validation) to simulate legacy data:
        // Jan: income 1.000.000
        $journalJan = app(JournalService::class)->createJournalEntry(
            'Pemasukan Januari', 'KAS-JAN', [
                ['account_code' => AccountCodes::CASH, 'debit' => 1000000, 'credit' => 0],
                ['account_code' => $revenue->account_code, 'debit' => 0, 'credit' => 1000000],
            ],
            $createdAt->copy()->subMonths(2)->startOfMonth()->toDateString(),
            $school->id
        );

        $kasJan = KasTransaction::create([
            'branch_id' => $school->id,
            'transaction_date' => $createdAt->copy()->subMonths(2)->startOfMonth(),
            'type' => 'pemasukan',
            'account_id' => $revenue->id,
            'amount' => 1000000,
            'description' => 'Pemasukan Januari',
            'journal_id' => $journalJan->id,
        ]);

        // Feb: expense 1.200.000
        $journalFeb = app(JournalService::class)->createJournalEntry(
            'Pengeluaran Februari', 'KAS-FEB', [
                ['account_code' => $expense->account_code, 'debit' => 1200000, 'credit' => 0],
                ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => 1200000],
            ],
            $createdAt->copy()->subMonth()->startOfMonth()->toDateString(),
            $school->id
        );

        KasTransaction::create([
            'branch_id' => $school->id,
            'transaction_date' => $createdAt->copy()->subMonth()->startOfMonth(),
            'type' => 'pengeluaran',
            'account_id' => $expense->id,
            'amount' => 1200000,
            'description' => 'Pengeluaran Februari',
            'journal_id' => $journalFeb->id,
        ]);

        // Mar: income 2.000.000
        $journalMar = app(JournalService::class)->createJournalEntry(
            'Pemasukan Maret', 'KAS-MAR', [
                ['account_code' => AccountCodes::CASH, 'debit' => 2000000, 'credit' => 0],
                ['account_code' => $revenue->account_code, 'debit' => 0, 'credit' => 2000000],
            ],
            $createdAt->copy()->startOfMonth()->toDateString(),
            $school->id
        );

        KasTransaction::create([
            'branch_id' => $school->id,
            'transaction_date' => $createdAt->copy()->startOfMonth(),
            'type' => 'pemasukan',
            'account_id' => $revenue->id,
            'amount' => 2000000,
            'description' => 'Pemasukan Maret',
            'journal_id' => $journalMar->id,
        ]);

        // Deleting January income would make Feb running balance = -200.000
        // even though final balance (after March) is 1.800.000.
        $this->delete(route('kas.destroy', $kasJan))
            ->assertSessionHasErrors('id');

        $this->assertDatabaseHas('kas_transactions', ['id' => $kasJan->id]);
    }
}
