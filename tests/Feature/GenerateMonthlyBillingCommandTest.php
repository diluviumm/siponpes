<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\SppTariff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GenerateMonthlyBillingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_billing_generates_invoice_and_sends_reminder(): void
    {
        Mail::fake();

        $kelas = Kelas::create([
            'nama_kelas' => 'X IPA',
            'wali_kelas' => 'Pak Budi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali@example.com',
        ]);

        SppTariff::create([
            'kelas_id' => $kelas->id,
            'branch_id' => null,
            'amount' => 200000,
            'academic_year' => now()->month >= 7 ? now()->year.'/'.(now()->year + 1) : (now()->year - 1).'/'.now()->year,
        ]);

        Artisan::call('billing:generate-monthly');

        $invoice = Invoice::first();

        $this->assertNotNull($invoice);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'santri_id' => $santri->id,
            'total_amount' => 200000,
            'status' => Invoice::STATUS_UNPAID,
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'unit_price' => 200000,
            'total_amount' => 200000,
        ]);

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
        ]);

        Mail::assertQueued(InvoiceReminderMail::class, function ($mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id;
        });
    }

    public function test_overdue_reminder_command_sends_reminders_for_unpaid_invoices(): void
    {
        Mail::fake();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI IPS',
            'wali_kelas' => 'Bu Santi',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'email' => 'wali2@example.com',
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-OVERDUE-001',
            'total_amount' => 180000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(5),
        ]);

        Artisan::call('billing:send-overdue-reminders');

        $this->assertDatabaseHas('invoice_reminders', [
            'invoice_id' => $invoice->id,
            'channel' => 'email',
        ]);

        Mail::assertQueued(InvoiceReminderMail::class, function ($mail) use ($santri, $invoice) {
            return $mail->hasTo($santri->email)
                && $mail->invoice->id === $invoice->id;
        });
    }
}
