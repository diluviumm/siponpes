<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Santri;
use App\Models\SppTariff;
use App\Services\InvoiceReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyBilling extends Command
{
    protected $signature = 'billing:generate-monthly';

    protected $description = 'Generate monthly SPP invoices for active santri.';

    public function handle()
    {
        $this->info('Starting monthly billing generation...');

        $monthLabel = Carbon::now()->format('Ym');

        $santris = Santri::with(['kelas', 'school'])->get();

        foreach ($santris as $santri) {
            $tariff = $this->findTariffForSantri($santri);

            if (! $tariff) {
                $this->warn("No tariff found for santri {$santri->nama} ({$santri->nis}). Skipping.");

                continue;
            }

            $invoiceNumber = $this->buildInvoiceNumber($santri, $monthLabel);
            $dueDate = Carbon::now()->endOfMonth();

            if (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                $this->info("Invoice already exists for {$invoiceNumber}. Skipping.");

                continue;
            }

            $invoice = DB::transaction(function () use ($santri, $tariff, $invoiceNumber, $dueDate) {
                $invoice = Invoice::create([
                    'santri_id' => $santri->id,
                    'invoice_number' => $invoiceNumber,
                    'total_amount' => $tariff->amount,
                    'status' => Invoice::STATUS_UNPAID,
                    'due_date' => $dueDate,
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'SPP Bulan '.Carbon::now()->translatedFormat('F Y'),
                    'quantity' => 1,
                    'unit_price' => $tariff->amount,
                    'total_amount' => $tariff->amount,
                ]);

                return $invoice;
            });

            app(InvoiceReminderService::class)->sendReminder(
                $invoice,
                $this->buildInvoiceReminderMessage($santri, $invoiceNumber, $tariff->amount, $dueDate)
            );

            $this->info("Created invoice {$invoiceNumber} for {$santri->nama}.");
        }

        $this->info('Monthly billing generation completed.');

        return 0;
    }

    protected function findTariffForSantri(Santri $santri): ?SppTariff
    {
        $academicYear = $this->currentAcademicYear();

        $classTariff = SppTariff::where('kelas_id', $santri->kelas_id)
            ->where('academic_year', $academicYear)
            ->first();

        if ($classTariff) {
            return $classTariff;
        }

        return SppTariff::whereNull('kelas_id')
            ->where('branch_id', $santri->branch_id)
            ->where('academic_year', $academicYear)
            ->first();
    }

    protected function buildInvoiceNumber(Santri $santri, string $monthLabel): string
    {
        return sprintf('INV-%s-%s-SPP', $santri->id, $monthLabel);
    }

    protected function currentAcademicYear(): string
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        if ($month >= 7) {
            return sprintf('%s/%s', $year, $year + 1);
        }

        return sprintf('%s/%s', $year - 1, $year);
    }

    protected function buildInvoiceReminderMessage(Santri $santri, string $invoiceNumber, float $amount, Carbon $dueDate): string
    {
        return sprintf(
            'Halo %s, tagihan SPP bulan ini dengan nomor %s sebesar Rp %s jatuh tempo pada %s.',
            $santri->nama,
            $invoiceNumber,
            number_format($amount, 2, ',', '.'),
            $dueDate->format('d-m-Y')
        );
    }
}
