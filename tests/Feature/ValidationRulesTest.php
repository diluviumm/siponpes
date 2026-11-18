<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class ValidationRulesTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function createSantriContext(string $namaKelas = '7A')
    {
        $school = $this->createSchool('Sekolah Validasi');
        $kelas = $this->newKelas($school, $namaKelas);
        $this->actingAsSchoolAdmin($school);

        return [$school, $kelas];
    }

    public function test_phone_accepts_indonesian_formats(): void
    {
        [, $kelas] = $this->createSantriContext();

        $this->post(route('santri.store'), [
            'nama' => 'Santri Telp Valid',
            'no_hp_wali' => '081234567890',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();

        $this->post(route('santri.store'), [
            'nama' => 'Santri Telp Valid 62',
            'no_hp_wali' => '+6281234567890',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_phone_rejects_invalid_formats(): void
    {
        [, $kelas] = $this->createSantriContext();

        $this->post(route('santri.store'), [
            'nama' => 'Santri Telp Invalid',
            'no_hp_wali' => '12345',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors('no_hp_wali');

        $this->post(route('santri.store'), [
            'nama' => 'Santri Telp Huruf',
            'no_hp_wali' => 'abc123',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors('no_hp_wali');
    }

    public function test_email_without_at_gets_warning(): void
    {
        [, $kelas] = $this->createSantriContext();

        $this->post(route('santri.store'), [
            'nama' => 'Santri Email Salah',
            'email' => 'bukanemail',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors(['email' => 'Format email tidak valid. Email harus mengandung "@", contoh: nama@sekolah.sch.id.']);
    }

    public function test_nis_max_length_is_10(): void
    {
        [, $kelas] = $this->createSantriContext();

        $this->post(route('santri.store'), [
            'nama' => 'Santri NIS Panjang',
            'nis' => '12345678901',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors(['nis' => 'NIS maksimal 10 karakter.']);

        $this->post(route('santri.store'), [
            'nama' => 'Santri NIS OK',
            'nis' => '1234567890',
            'kelas_id' => $kelas->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_academic_year_requires_correct_format(): void
    {
        $school = $this->createSchool('Sekolah Tahun Ajaran');
        $this->actingAsSuperadmin();

        $this->post(route('spp_tariffs.store'), [
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025-2026',
            'amount' => 100000,
        ])->assertSessionHasErrors('academic_year');

        $this->post(route('spp_tariffs.store'), [
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2027',
            'amount' => 100000,
        ])->assertSessionHasErrors('academic_year');

        $this->post(route('spp_tariffs.store'), [
            'branch_id' => $school->id,
            'type' => 'semua',
            'periode_type' => 'bulanan',
            'academic_year' => '2025/2026',
            'amount' => 100000,
        ])->assertSessionHasNoErrors();
    }
}
