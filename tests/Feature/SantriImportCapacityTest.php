<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SantriImportCapacityTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_import_to_almost_full_class_succeeds(): void
    {
        $school = $this->createSchool('Sekolah Import Sisa');
        $kelas = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 3,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->count(2)->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile(['Adiba Shakila Ramadhani', 'Aira Kalila'], '7A');

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 1
                && count($result['errors']) === 1;
        });

        // Only one new santri added (the second exceeded capacity)
        $this->assertSame(3, Santri::where('kelas_id', $kelas->id)->count());
        // Kapasitas tidak berubah
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'kapasitas' => 3]);
    }

    public function test_import_to_full_class_is_rejected_with_error(): void
    {
        $school = $this->createSchool('Sekolah Import Penuh');
        $kelas = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 2,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->count(2)->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile(['Budi Santoso'], '7A');

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 0
                && count($result['errors']) === 1
                && str_contains($result['errors'][0], 'sudah penuh');
        });

        $this->assertSame(2, Santri::where('kelas_id', $kelas->id)->count());
    }

    public function test_import_multiple_students_stops_at_capacity(): void
    {
        $school = $this->createSchool('Sekolah Import Bertahap');
        $kelas = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 2,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        // Try importing 3 new students — only 1 should fit (capacity = 2, 1 existing)
        $filePath = $this->createImportFile(['Siswa Baru A', 'Siswa Baru B', 'Siswa Baru C'], '7A');

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 1
                && count($result['errors']) === 2;
        });

        $this->assertSame(2, Santri::where('kelas_id', $kelas->id)->count());
    }

    public function test_import_preserves_existing_capacity(): void
    {
        $school = $this->createSchool('Sekolah Import Kapasitas Tetap');
        $kelas = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 10,
            'branch_id' => $school->id,
        ]);

        Santri::factory()->count(3)->create([
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile(['Siswa Baru X', 'Siswa Baru Y'], '7A');

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 2;
        });

        $this->assertSame(5, Santri::where('kelas_id', $kelas->id)->count());
        // Kapasitas harus tetap 10, tidak berubah menjadi 5
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'kapasitas' => 10]);
    }

    public function test_import_rejects_moving_santri_to_full_class(): void
    {
        $school = $this->createSchool('Sekolah Import Pindah Penuh');
        $kelasAsal = Kelas::create([
            'nama_kelas' => '7A',
            'wali_kelas' => '-',
            'kapasitas' => 2,
            'branch_id' => $school->id,
        ]);
        $kelasTujuan = Kelas::create([
            'nama_kelas' => '7B',
            'wali_kelas' => '-',
            'kapasitas' => 1,
            'branch_id' => $school->id,
        ]);

        $santri = Santri::factory()->create([
            'nama' => 'Adiba Shakila Ramadhani',
            'kelas_id' => $kelasAsal->id,
            'branch_id' => $school->id,
        ]);

        // Fill the target class
        Santri::factory()->create([
            'kelas_id' => $kelasTujuan->id,
            'branch_id' => $school->id,
        ]);

        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile(['Adiba Shakila Ramadhani'], '7B');

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 0
                && $result['kelas_diperbaiki'] === 0
                && count($result['errors']) === 1;
        });

        // Santri should still be in original class
        $this->assertDatabaseHas('santris', ['id' => $santri->id, 'kelas_id' => $kelasAsal->id]);
    }

    public function test_new_class_from_import_gets_capacity_set_to_count(): void
    {
        $school = $this->createSchool('Sekolah Import Kelas Baru');
        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile(['Siswa Alpha', 'Siswa Beta']);

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile($filePath, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionHas('importResult', function (array $result) {
            return $result['created_kelas'] === 1 && $result['created_santri'] === 2;
        });

        $kelas = Kelas::where('nama_kelas', '7C')->first();
        $this->assertNotNull($kelas);
        // New class from import: kapasitas should be set to count
        $this->assertSame(2, $kelas->kapasitas);
    }

    protected function createImportFile(array $names, string $kelasName = '7C'): string
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Siswa');
        $sheet->setCellValue('C4', 'Nama');
        $sheet->setCellValue('D4', 'Kelas');

        $row = 5;
        foreach ($names as $name) {
            $sheet->setCellValue('C'.$row, $name);
            $sheet->setCellValue('D'.$row, $kelasName);
            $row++;
        }

        $path = storage_path('app/private/imports-test/test-capacity-'.uniqid().'.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
