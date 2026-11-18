<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Santri;
use App\Services\InvoiceReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_email_and_records_email_channel_when_santri_has_email(): void
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'X IPA',
            'wali_kelas' => 'Pak Budi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali@example.com',
            'no_hp_wali' => '081234567890',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-TEST-0001',
            'total_amount' => 150000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        Mail::fake();

        app(InvoiceReminderService::class)->sendReminder($invoice, 'Reminder invoice testing.');

        Mail::assertQueued(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id
                && $mail->messageText === 'Reminder invoice testing.';
        });

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'message' => 'Reminder invoice testing.',
        ]);
    }

    public function test_it_records_none_channel_when_no_email_and_whatsapp_not_configured(): void
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'XI IPS',
            'wali_kelas' => 'Bu Santi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => null,
            'no_hp_wali' => '081234567891',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-TEST-0002',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        Mail::fake();

        app(InvoiceReminderService::class)->sendReminder($invoice, 'Reminder invoice testing WA fallback.');

        Mail::assertNothingQueued();

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'none',
            'message' => 'Reminder invoice testing WA fallback.',
        ]);
    }

    public function test_it_records_none_channel_when_no_contact_method_exists(): void
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'XII MIPA',
            'wali_kelas' => 'Pak Agus',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => null,
            'no_hp_wali' => null,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-TEST-0003',
            'total_amount' => 130000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(7),
        ]);

        Mail::fake();

        app(InvoiceReminderService::class)->sendReminder($invoice, 'Reminder invoice no contact.');

        Mail::assertNothingQueued();

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'none',
            'message' => 'Reminder invoice no contact.',
        ]);
    }
}
