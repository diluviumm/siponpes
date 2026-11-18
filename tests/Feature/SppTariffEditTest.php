<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Santri;
use App\Models\SppTariff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SppTariffEditTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function kelas($school, string $nama): Kelas
    {
        return Kelas::create([
            'nama_kelas' => $nama,
            'wali_kelas' => '-',
            'kapasitas' => 30,
            'branch_id' => $school->id,
        ]);
    }

    public function test_can_create_tariff_for_all_classes(): void
    {
        $school = $this->createSchool('Sekolah Tarif Semua');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('spp_tariffs.store'), [
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 150000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'branch_id' => $school->id,
            'type' => 'semua',
            'kelas_id' => null,
            'angkatan' => null,
            'amount' => 150000,
        ]);
    }

    public function test_can_create_tariff_per_angkatan_from_database_options(): void
    {
        $school = $this->createSchool('Sekolah Tarif Angkatan');
        $kelas = $this->kelas($school, 'X IPA');

        Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
            'angkatan' => 10,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('spp_tariffs.create'))
            ->assertOk()
            ->assertSee('Kelas 10');

        $this->post(route('spp_tariffs.store'), [
            'type' => 'angkatan',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'angkatan' => 10,
            'amount' => 175000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'branch_id' => $school->id,
            'type' => 'angkatan',
            'angkatan' => 10,
            'kelas_id' => null,
            'amount' => 175000,
        ]);
    }

    public function test_can_create_tariff_per_kelas(): void
    {
        $school = $this->createSchool('Sekolah Tarif Kelas');
        $kelas = $this->kelas($school, 'X IPA');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('spp_tariffs.store'), [
            'type' => 'kelas',
            'periode_type' => 'semester',
            'academic_year' => '2025/2026',
            'kelas_id' => $kelas->id,
            'amount' => 200000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'branch_id' => $school->id,
            'type' => 'kelas',
            'kelas_id' => $kelas->id,
            'angkatan' => null,
            'periode_type' => 'semester',
        ]);
    }

    public function test_edit_form_shows_saved_angkatan_and_can_be_changed(): void
    {
        $school = $this->createSchool('Sekolah Edit Angkatan');
        $this->actingAsSchoolAdmin($school);

        $tariff = SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'angkatan',
            'angkatan' => 10,
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $this->get(route('spp_tariffs.edit', $tariff))
            ->assertOk()
            ->assertSee('Kelas 10')
            ->assertSee('value="10"', false);

        $this->put(route('spp_tariffs.update', $tariff), [
            'type' => 'angkatan',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'angkatan' => 11,
            'amount' => 125000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'id' => $tariff->id,
            'angkatan' => 11,
            'amount' => 125000,
        ]);
    }

    public function test_edit_form_shows_saved_kelas_and_can_be_changed(): void
    {
        $school = $this->createSchool('Sekolah Edit Kelas');
        $kelasA = $this->kelas($school, 'X IPA');
        $kelasB = $this->kelas($school, 'XI IPA');
        $this->actingAsSchoolAdmin($school);

        $tariff = SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'kelas',
            'kelas_id' => $kelasA->id,
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $this->get(route('spp_tariffs.edit', $tariff))
            ->assertOk()
            ->assertSee('X IPA');

        $this->put(route('spp_tariffs.update', $tariff), [
            'type' => 'kelas',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'kelas_id' => $kelasB->id,
            'amount' => 110000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'id' => $tariff->id,
            'kelas_id' => $kelasB->id,
            'amount' => 110000,
        ]);
    }

    public function test_edit_can_change_scope_to_all_classes(): void
    {
        $school = $this->createSchool('Sekolah Edit Scope');
        $kelas = $this->kelas($school, 'X IPA');
        $this->actingAsSchoolAdmin($school);

        $tariff = SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'kelas',
            'kelas_id' => $kelas->id,
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $this->put(route('spp_tariffs.update', $tariff), [
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 90000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('spp_tariffs', [
            'id' => $tariff->id,
            'type' => 'semua',
            'kelas_id' => null,
            'angkatan' => null,
            'amount' => 90000,
        ]);
    }

    public function test_duplicate_tariff_scope_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Tarif Ganda');
        $this->actingAsSchoolAdmin($school);

        SppTariff::create([
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $this->post(route('spp_tariffs.store'), [
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 120000,
        ])->assertSessionHasErrors('type');

        $this->assertSame(1, SppTariff::where('branch_id', $school->id)->count());
    }

    public function test_school_admin_cannot_edit_tariff_of_another_school(): void
    {
        $schoolA = $this->createSchool('Sekolah Tarif A');
        $schoolB = $this->createSchool('Sekolah Tarif B');

        $tariffB = SppTariff::create([
            'branch_id' => $schoolB->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->get(route('spp_tariffs.edit', $tariffB))->assertNotFound();
    }
}
