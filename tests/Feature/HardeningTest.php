<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Mail\PasswordResetMail;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Santri;
use App\Models\Wallet;
use App\Services\InvoiceReminderService;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_school_admin_cannot_create_invoice_for_other_school_santri(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');
        $kelasB = $this->newKelas($schoolB, '7B');

        $santriB = Santri::factory()->create([
            'nama' => 'Anak B',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->post(route('invoices.store'), [
            'santri_id' => $santriB->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 50000,
            'description' => 'SPP',
            'status' => 'unpaid',
        ])->assertSessionHasErrors('santri_id');

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_school_admin_can_create_invoice_for_own_school_santri(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $kelasA = $this->newKelas($schoolA, '7A');

        $santriA = Santri::factory()->create([
            'nama' => 'Anak A',
            'kelas_id' => $kelasA->id,
            'branch_id' => $schoolA->id,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->post(route('invoices.store'), [
            'santri_id' => $santriA->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 50000,
            'description' => 'SPP',
            'status' => 'unpaid',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('invoices', [
            'santri_id' => $santriA->id,
            'total_amount' => 50000,
        ]);
    }

    public function test_school_admin_cannot_move_invoice_to_other_school_santri(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');
        $kelasA = $this->newKelas($schoolA, '7A');
        $kelasB = $this->newKelas($schoolB, '7B');

        $santriA = Santri::factory()->create([
            'nama' => 'Anak A',
            'kelas_id' => $kelasA->id,
            'branch_id' => $schoolA->id,
        ]);

        $santriB = Santri::factory()->create([
            'nama' => 'Anak B',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santriA->id,
            'invoice_number' => 'INV-MOVE-001',
            'total_amount' => 50000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->put(route('invoices.update', $invoice), [
            'santri_id' => $santriB->id,
            'status' => 'unpaid',
        ])->assertSessionHasErrors('santri_id');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'santri_id' => $santriA->id,
        ]);
    }

    public function test_cannot_delete_kelas_with_santri(): void
    {
        $school = $this->createSchool();
        $kelas = $this->newKelas($school, '7A');

        Santri::factory()->create([
            'nama' => 'Anak Kelas',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSuperadmin();

        $this->delete(route('kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id]);
    }

    public function test_can_delete_empty_kelas(): void
    {
        $school = $this->createSchool();
        $kelas = $this->newKelas($school, '8B');

        $this->actingAsSuperadmin();

        $this->delete(route('kelas.destroy', $kelas))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('kelas', ['id' => $kelas->id]);
    }

    public function test_soft_deleted_santri_is_hidden_from_listing_and_totals(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah Soft');
        $kelas = $this->newKelas($school, '7A');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Soft',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create(['santri_id' => $santri->id, 'balance' => 50000, 'daily_spending_limit' => 0]);

        $this->delete(route('santri.destroy', $santri))
            ->assertRedirect();

        $this->assertSoftDeleted('santris', ['id' => $santri->id]);

        $this->get(route('dashboard', ['school_id' => $school->id]))
            ->assertOk()
            ->assertDontSee('Rp 50.000,00');
    }

    public function test_reminder_is_not_resent_within_24_hours(): void
    {
        $school = $this->createSchool();
        $kelas = $this->newKelas($school, '7A');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Reminder',
            'email' => 'wali@example.com',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-DEDUP-001',
            'total_amount' => 100000,
            'status' => 'unpaid',
            'due_date' => now()->subDays(2),
        ]);

        InvoiceReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'message' => 'Pengingat sebelumnya',
            'sent_at' => now()->subHour(),
        ]);

        Mail::fake();

        $service = app(InvoiceReminderService::class);

        $this->assertSame('skipped', $service->sendReminder($invoice, 'Pengingat kedua'));

        $this->assertSame('email', $service->sendReminder($invoice, 'Pengingat paksa', force: true));

        $this->assertDatabaseCount('invoice_reminders', 2);
        Mail::assertQueued(InvoiceReminderMail::class, 1);
    }

    public function test_password_reset_flow_resets_password(): void
    {
        Mail::fake();

        $school = $this->createSchool();
        $user = $this->createSchoolAdmin($school, 'reset@test.sch.id');

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status');

        Mail::assertQueued(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        $token = app('auth.password.broker')->createToken($user);
        $this->assertNotEmpty($token);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'passwordbaru123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_unverified_user_is_redirected_to_verification_notice(): void
    {
        $school = $this->createSchool();
        $user = $this->createSchoolAdmin($school, 'unverified@test.sch.id');

        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk();
    }

    public function test_email_verification_link_marks_user_verified(): void
    {
        $school = $this->createSchool();
        $user = $this->createSchoolAdmin($school, 'verify@test.sch.id');

        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($url)->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_link_works_without_login(): void
    {
        $school = $this->createSchool();
        $user = $this->createSchoolAdmin($school, 'guest-verify@test.sch.id');

        $user->forceFill(['email_verified_at' => null])->save();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($url)->assertRedirect(route('login'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_register_honeypot_blocks_bot(): void
    {
        $this->post(route('register.store'), [
            'website' => 'http://spam.example.com',
            'school_name' => 'Sekolah Bot',
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('branches', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_security_headers_are_present(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_general_journal_respects_date_period_filter(): void
    {
        $this->actingAsSuperadmin();

        $service = app(JournalService::class);

        $service->createJournalEntry(
            'Jurnal Januari',
            'REF-JAN-001',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ],
            '2026-01-15'
        );

        $service->createJournalEntry(
            'Jurnal Maret',
            'REF-MAR-001',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 50000],
            ],
            '2026-03-15'
        );

        $response = $this->get(route('financial.journal', [
            'from' => '2026-02-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk()
            ->assertSee('REF-MAR-001')
            ->assertDontSee('REF-JAN-001');
    }

    public function test_canteen_transaction_rejects_insufficient_balance(): void
    {
        $school = $this->createSchool('Sekolah Kantin C');
        $kelas = $this->newKelas($school, 'XII IPA');

        $santri = Santri::factory()->create([
            'santri_card_token' => 'card-token-low',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 5000,
            'daily_spending_limit' => 0,
        ]);

        $this->postJson('/api/canteen/transaction', [
            'santri_card_token' => 'card-token-low',
            'amount' => 20000,
            'canteen_name' => 'Kantin C',
        ], [
            'X-API-KEY' => $school->api_key,
        ])->assertStatus(400)
            ->assertJson(['message' => 'Insufficient wallet balance']);
    }

    public function test_expire_overdue_command_marks_invoices_expired(): void
    {
        $school = $this->createSchool();
        $kelas = $this->newKelas($school, '7A');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Overdue',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-EXP-001',
            'total_amount' => 100000,
            'status' => 'unpaid',
            'due_date' => now()->subDays(10),
        ]);

        Artisan::call('billing:expire-overdue');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'expired',
        ]);
    }
}
