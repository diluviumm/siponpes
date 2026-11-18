<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class PrintExportTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_invoice_receipt_can_be_printed_as_pdf(): void
    {
        $school = $this->createSchool('Sekolah Cetak');
        $kelas = $this->newKelas($school, '7A');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Cetak',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $invoice = Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-PRINT-001',
            'total_amount' => 50000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(7),
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('invoices.print', $invoice))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invoice_list_can_be_exported_as_neat_pdf(): void
    {
        $school = $this->createSchool('Sekolah Cetak B');
        $kelas = $this->newKelas($school, '7B');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Cetak B',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-PRINT-B',
            'total_amount' => 75000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(3),
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('invoices.export', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_ledger_can_be_exported_as_pdf_without_selecting_account(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('financial.ledger', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_all_financial_reports_can_be_exported_as_excel(): void
    {
        $this->actingAsSuperadmin();

        $excelType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $this->get(route('financial.journal', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', $excelType);

        $this->get(route('financial.ledger', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', $excelType);

        $this->get(route('financial.trial_balance', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', $excelType);

        $this->get(route('financial.balance_sheet', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', $excelType);

        $this->get(route('financial.income_statement', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', $excelType);
    }

    public function test_santri_list_can_be_exported_as_csv_and_pdf(): void
    {
        $school = $this->createSchool('Sekolah Cetak Santri');
        $kelas = $this->newKelas($school, '7Z');

        Santri::factory()->create([
            'nama' => 'Anak Export',
            'nis' => 'EXP001',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $csv = $this->get(route('santri.index', ['csv' => 1]));
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $csv->headers->get('Content-Disposition'));

        $this->get(route('santri.index', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
