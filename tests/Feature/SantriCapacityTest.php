<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SantriCapacityTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function kelasWithCapacity($school, string $nama, int $kapasitas): Kelas
    {
        return Kelas::create([
            'nama_kelas' => $nama,
            'wali_kelas' => '-',
            'kapasitas' => $kapasitas,
            'branch_id' => $school->id,
        ]);
    }

    public function test_santri_can_be_added_when_capacity_is_available(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas A');
        $kelas = $this->kelasWithCapacity($school, '7A', 2);
        $this->actingAsSchoolAdmin($school);

        $this->post(route('santri.store'), [
            'nama' => 'Santri Satu',
            'kelas_id' => $kelas->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('santris', [
            'nama' => 'Santri Satu',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);
    }

    public function test_adding_santri_is_rejected_when_class_is_full(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas B');
        $kelas = $this->kelasWithCapacity($school, '7A', 2);

        Santri::factory()->count(2)->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('santri.store'), [
            'nama' => 'Santri Ketiga',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors([
            'kelas_id' => 'Kapasitas kelas sudah penuh. Tidak dapat menambahkan santri lagi.',
        ]);

        $this->assertDatabaseMissing('santris', ['nama' => 'Santri Ketiga']);
        $this->assertSame(2, Santri::where('kelas_id', $kelas->id)->count());
    }

    public function test_editing_santri_without_changing_class_does_not_count_as_new_member(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas C');
        $kelas = $this->kelasWithCapacity($school, '7A', 2);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->put(route('santri.update', $santri), [
            'nama' => 'Nama Diperbarui',
            'kelas_id' => $kelas->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('santris', [
            'id' => $santri->id,
            'nama' => 'Nama Diperbarui',
            'kelas_id' => $kelas->id,
        ]);
    }

    public function test_moving_santri_to_available_class_succeeds(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas D');
        $kelasAsal = $this->kelasWithCapacity($school, '7A', 2);
        $kelasTujuan = $this->kelasWithCapacity($school, '7B', 2);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelasAsal->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->put(route('santri.update', $santri), [
            'nama' => $santri->nama,
            'kelas_id' => $kelasTujuan->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('santris', [
            'id' => $santri->id,
            'kelas_id' => $kelasTujuan->id,
        ]);
    }

    public function test_moving_santri_to_full_class_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas E');
        $kelasAsal = $this->kelasWithCapacity($school, '7A', 2);
        $kelasTujuan = $this->kelasWithCapacity($school, '7B', 1);

        $santri = Santri::factory()->create([
            'kelas_id' => $kelasAsal->id,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->create([
            'kelas_id' => $kelasTujuan->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->put(route('santri.update', $santri), [
            'nama' => $santri->nama,
            'kelas_id' => $kelasTujuan->id,
        ])->assertSessionHasErrors('kelas_id');

        $this->assertDatabaseHas('santris', [
            'id' => $santri->id,
            'kelas_id' => $kelasAsal->id,
        ]);
    }

    public function test_capacity_cannot_be_reduced_below_registered_santri_count(): void
    {
        $school = $this->createSchool('Sekolah Kapasitas F');
        $kelas = $this->kelasWithCapacity($school, '7A', 3);

        Santri::factory()->count(2)->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->put(route('kelas.update', $kelas), [
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 1,
        ])->assertSessionHasErrors('kapasitas');

        $this->assertDatabaseHas('kelas', [
            'id' => $kelas->id,
            'kapasitas' => 3,
        ]);
    }
}
