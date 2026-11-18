<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_dashboard_displays_invoice_and_reminder_summary_metrics(): void
    {
        $this->actingAsSuperadmin();

        $kelas = Kelas::create([
            'nama_kelas' => 'XI IPA',
            'wali_kelas' => 'Pak Agus',
            'kapasitas' => 30,
        ]);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
        ]);

        Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-1',
            'total_amount' => 100000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->subDays(3),
        ]);

        Invoice::create([
            'santri_id' => $santri->id,
            'invoice_number' => 'INV-2',
            'total_amount' => 120000,
            'status' => Invoice::STATUS_UNPAID,
            'due_date' => now()->addDays(5),
        ]);

        InvoiceReminder::create([
            'invoice_id' => Invoice::first()->id,
            'channel' => 'wa',
            'message' => 'Reminder test',
            'sent_at' => now()->subDays(2),
        ]);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Total Invoice');
        $response->assertSee('2');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Overdue');
        $response->assertSee('Jatuh Tempo 7 Hari');
        $response->assertSee('Pengingat 7 Hari');
    }

    public function test_superadmin_dashboard_can_scope_stats_to_one_school(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah A');
        $kelas = $this->newKelas($school, 'VII A');
        Santri::factory()->create(['branch_id' => $school->id, 'kelas_id' => $kelas->id]);

        $response = $this->get(route('dashboard', ['school_id' => $school->id]));

        $response->assertStatus(200);
        $response->assertSee('Sekolah Terpilih');
        $response->assertSee($school->name);
        $response->assertSee('Total Kelas');
        $response->assertDontSee('Total Sekolah');
        $response->assertDontSee('Statistik per Sekolah');
    }

    public function test_school_admin_dashboard_renders_without_school_dropdown(): void
    {
        $school = $this->createSchool('Sekolah B');
        $this->actingAsSchoolAdmin($school);

        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Sekolah Anda');
        $response->assertSee($school->name);
        $response->assertSee('Total Kelas');
        $response->assertDontSee('Semua Sekolah (Gabungan)');
        $response->assertDontSee('Statistik per Sekolah');
    }
}
