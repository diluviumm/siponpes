<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_remind_route_sends_email_and_records_reminder(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI IPA',
            'wali_kelas' => 'Pak Arif',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-reminder@example.com',
            'no_hp_wali' => '081234567899',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-REMIND-0001',
            'total_amount' => 160000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(3),
        ]);

        $response = $this->post(route('invoices.remind', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $response->assertSessionHas('success');

        Mail::assertQueued(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id
                && $mail->messageText === sprintf(
                    'Halo %s, tagihan Anda dengan nomor %s sebesar Rp %s jatuh tempo pada %s. Mohon lakukan pembayaran segera.',
                    $santri->nama,
                    $invoice->invoice_number,
                    number_format($invoice->total_amount, 2, ',', '.'),
                    $invoice->due_date->format('d/m/Y')
                );
        });

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
        ]);
    }

    public function test_remind_overdue_route_sends_reminders_for_unpaid_invoices(): void
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
            'email' => 'wali-overdue@example.com',
            'no_hp_wali' => '081234567898',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-OVERDUE-001',
            'total_amount' => 180000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(5),
        ]);

        $response = $this->post(route('invoices.remind-overdue'));

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success');

        Mail::assertQueued(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id;
        });

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
        ]);
    }

    public function test_remind_overdue_route_also_sends_reminders_for_invoices_due_today(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Bahasa',
            'wali_kelas' => 'Bu Nina',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-today@example.com',
            'no_hp_wali' => '081234567897',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-DUETODAY-001',
            'total_amount' => 200000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->post(route('invoices.remind-overdue'));

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success');

        Mail::assertQueued(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id;
        });

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
        ]);
    }

    public function test_remind_all_unpaid_route_sends_to_every_unpaid_invoice(): void
    {
        Mail::fake();
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Otomotif',
            'wali_kelas' => 'Pak Doni',
            'kapasitas' => 30,
        ]);

        $santriFuture = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-future@example.com',
            'no_hp_wali' => '081234567896',
        ]);

        $invoiceFuture = Invoice::create([
            'santri_id' => $santriFuture->id,
            'invoice_number' => 'INV-FUTURE-001',
            'total_amount' => 190000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(10),
        ]);

        $santriOverdue = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-overdue2@example.com',
            'no_hp_wali' => '081234567895',
        ]);

        $invoiceOverdue = Invoice::create([
            'santri_id' => $santriOverdue->id,
            'invoice_number' => 'INV-OVERDUE-002',
            'total_amount' => 210000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(3),
        ]);

        $santriPaid = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-paid@example.com',
        ]);

        $invoicePaid = Invoice::create([
            'santri_id' => $santriPaid->id,
            'invoice_number' => 'INV-PAID-001',
            'total_amount' => 220000,
            'status' => Invoice::STATUS_PAID,
            'due_date' => now()->subDays(1),
        ]);

        $response = $this->post(route('invoices.remind-all-unpaid'));

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success');

        Mail::assertQueued(InvoiceReminderMail::class, 2);

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoiceFuture->id,
            'channel' => 'email',
        ]);

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoiceOverdue->id,
            'channel' => 'email',
        ]);

        $this->assertDatabaseMissing('invoice_reminders', [
            'invoice_id' => $invoicePaid->id,
        ]);
    }

    public function test_remind_all_unpaid_respects_branch_filter(): void
    {
        Mail::fake();

        $schoolA = $this->createSchool('Sekolah A Pengingat');
        $kelasA = $this->newKelas($schoolA, '7A');

        $santriA = Santri::factory()->create([
            'kelas_id' => $kelasA->id,
            'branch_id' => $schoolA->id,
            'email' => 'wali-a@example.com',
        ]);

        $invoiceA = Invoice::create([
            'santri_id' => $santriA->id,
            'invoice_number' => 'INV-A-001',
            'total_amount' => 100000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(5),
        ]);

        $schoolB = $this->createSchool('Sekolah B Pengingat');
        $kelasB = $this->newKelas($schoolB, '7B');

        $santriB = Santri::factory()->create([
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
            'email' => 'wali-b@example.com',
        ]);

        $invoiceB = Invoice::create([
            'santri_id' => $santriB->id,
            'invoice_number' => 'INV-B-001',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(5),
        ]);

        $this->actingAsSuperadmin();

        $this->post(route('invoices.remind-all-unpaid'), ['branch_id' => $schoolA->id])
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        Mail::assertQueued(InvoiceReminderMail::class, 1);

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoiceA->id,
        ]);

        $this->assertDatabaseMissing('invoice_reminders', [
            'invoice_id' => $invoiceB->id,
        ]);
    }

    public function test_expire_overdue_invoices_command_marks_invoices_as_expired(): void
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'XII IPS',
            'wali_kelas' => 'Pak Ban',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali-expire@example.com',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-EXPIRE-001',
            'total_amount' => 170000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(2),
        ]);

        Artisan::call('billing:expire-overdue');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_EXPIRED,
        ]);
    }

    public function test_store_ignores_paid_status_and_creates_invoice_as_unpaid(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Akselerasi',
            'wali_kelas' => 'Bu Mega',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        $this->post(route('invoices.store'), [
            'santri_id' => $santri->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'amount' => 90000,
            'description' => 'SPP',
            'status' => Invoice::STATUS_PAID,
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'santri_id' => $santri->id,
            'total_amount' => 90000,
            'status' => Invoice::STATUS_UNPAID,
        ]);
    }

    public function test_update_cannot_set_invoice_status_to_paid(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Reguler',
            'wali_kelas' => 'Pak Dedi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-NOPAID-001',
            'total_amount' => 80000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        $this->put(route('invoices.update', $invoice), [
            'amount' => 80000,
            'status' => Invoice::STATUS_PAID,
        ])->assertSessionHasErrors('status');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_UNPAID,
        ]);
    }

    public function test_paid_invoice_cannot_be_edited_or_updated(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Prestasi',
            'wali_kelas' => 'Bu Rina',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-PAIDLOCK-001',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_PAID,
            'due_date' => now()->addDays(7),
        ]);

        $this->get(route('invoices.edit', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->put(route('invoices.update', $invoice), [
            'amount' => 50000,
            'status' => Invoice::STATUS_UNPAID,
        ])->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'total_amount' => 120000,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    public function test_paid_invoice_cannot_be_deleted(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Permanen',
            'wali_kelas' => 'Bu Permanen',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-PAIDDEL-001',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_PAID,
            'due_date' => now()->addDays(7),
        ]);

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_PAID,
        ]);
    }

    public function test_unpaid_invoice_can_be_deleted(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Sementara',
            'wali_kelas' => 'Bu Sementara',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-UNPAIDDEL-001',
            'total_amount' => 90000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        $this->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
    }

    public function test_pay_invoice_of_soft_deleted_santri_is_blocked(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI Yatim',
            'wali_kelas' => 'Bu Yatim',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 200000,
            'daily_spending_limit' => 0,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-YATIM-001',
            'total_amount' => 50000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        $santri->delete();

        $this->post(route('invoices.pay', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $santri->id,
            'balance' => 200000,
        ]);
    }
}
