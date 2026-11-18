<?php

namespace Tests\Feature;

use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SantriListTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_school_admin_can_view_all_santri_across_classes(): void
    {
        $school = $this->createSchool('Sekolah List Santri');
        $kelasA = $this->newKelas($school, 'Kelas A');
        $kelasB = $this->newKelas($school, 'Kelas B');

        Santri::factory()->create(['nama' => 'Anak Kelas A', 'kelas_id' => $kelasA->id, 'branch_id' => $school->id]);
        Santri::factory()->create(['nama' => 'Anak Kelas B', 'kelas_id' => $kelasB->id, 'branch_id' => $school->id]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('santri.index', ['kelas_id' => 'all']))
            ->assertOk()
            ->assertSee('Anak Kelas A')
            ->assertSee('Anak Kelas B');
    }

    public function test_school_admin_default_view_shows_class_picker_grid(): void
    {
        $school = $this->createSchool('Sekolah Picker Santri');
        $kelas = $this->newKelas($school, 'Kelas Picker');

        Santri::factory()->create(['nama' => 'Anak Picker', 'kelas_id' => $kelas->id, 'branch_id' => $school->id]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('santri.index'))
            ->assertOk()
            ->assertSee('Kelas Picker')
            ->assertSee('Semua Santri');
    }

    public function test_show_all_respects_search_filter(): void
    {
        $school = $this->createSchool('Sekolah Cari Semua');
        $kelasA = $this->newKelas($school, 'Kelas A1');
        $kelasB = $this->newKelas($school, 'Kelas B1');

        Santri::factory()->create(['nama' => 'Budi Santoso', 'nis' => '001', 'kelas_id' => $kelasA->id, 'branch_id' => $school->id]);
        Santri::factory()->create(['nama' => 'Andi Wijaya', 'nis' => '002', 'kelas_id' => $kelasB->id, 'branch_id' => $school->id]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('santri.index', ['kelas_id' => 'all', 'search' => 'Budi']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Andi Wijaya');
    }

    public function test_show_all_export_includes_all_classes(): void
    {
        $school = $this->createSchool('Sekolah Export Semua');
        $kelasA = $this->newKelas($school, 'Kelas A2');
        $kelasB = $this->newKelas($school, 'Kelas B2');

        Santri::factory()->create(['nama' => 'Anak Export A', 'nis' => '011', 'kelas_id' => $kelasA->id, 'branch_id' => $school->id]);
        Santri::factory()->create(['nama' => 'Anak Export B', 'nis' => '012', 'kelas_id' => $kelasB->id, 'branch_id' => $school->id]);

        $this->actingAsSchoolAdmin($school);

        $response = $this->get(route('santri.index', ['kelas_id' => 'all', 'csv' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $this->assertStringContainsString('Anak Export A', $response->streamedContent());
        $this->assertStringContainsString('Anak Export B', $response->streamedContent());
    }
}
