<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SantriImportTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_import_creates_kelas_santri_and_wallet_transactions(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $filePath = $this->createImportFile();

        $response = $this->post(route('santri.import.process'), [
            'file' => new UploadedFile(
                $filePath,
                'Setor Tabungan.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ]);

        $response->assertRedirect(route('santri.import'));
        $response->assertSessionHas('importResult');

        $this->assertDatabaseHas('kelas', ['nama_kelas' => '7C']);

        $this->assertDatabaseHas('santris', ['nama' => 'Adiba Shakila Ramadhani']);
        $this->assertDatabaseHas('santris', ['nama' => 'Aira Kalila']);

        $adiba = Santri::where('nama', 'Adiba Shakila Ramadhani')->first();
        $aira = Santri::where('nama', 'Aira Kalila')->first();

        $this->assertNotNull($adiba);
        $this->assertNotNull($aira);

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $adiba->id,
            'balance' => 40000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'santri_id' => $aira->id,
            'balance' => 15000,
        ]);

        $this->assertDatabaseCount('wallet_transactions', 4);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $adiba->id,
            'amount' => 20000,
            'type' => 'credit',
            'description' => 'Setoran tabungan bulan Juni (import)',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $adiba->id,
            'amount' => 30000,
            'type' => 'credit',
            'description' => 'Setoran tabungan bulan Juli (import)',
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $adiba->id,
            'amount' => 10000,
            'type' => 'debit',
            'description' => 'Penarikan tabungan bulan Juli (import)',
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 65000,
            'credit' => 0,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 0,
            'credit' => 65000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'debit' => 10000,
            'credit' => 0,
        ]);
    }

    public function test_import_adds_tabungan_to_existing_santri_without_duplicating(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas = Kelas::create([
            'nama_kelas' => '7C',
            'wali_kelas' => '-',
            'kapasitas' => 0,
            'branch_id' => $school->id,
        ]);

        $santri = Santri::create([
            'nama' => 'Adiba Shakila Ramadhani',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $wallet = Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 0,
            'daily_spending_limit' => 0,
        ]);

        $filePath = $this->createImportFile();

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile(
                $filePath,
                'Setor Tabungan.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])->assertSessionHas('importResult', function (array $result) {
            return $result['created_santri'] === 1
                && $result['existing_santri'] === 1
                && $result['transactions_created'] === 4;
        });

        $this->assertDatabaseCount('santris', 2);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 40000,
        ]);

        $this->assertDatabaseCount('wallet_transactions', 4);

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile(
                $filePath,
                'Setor Tabungan.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])->assertSessionHas('importResult', function (array $result) {
            return $result['transactions_created'] === 0;
        });

        $this->assertDatabaseCount('wallet_transactions', 4);
        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 40000,
        ]);
    }

    public function test_import_corrects_kelas_of_existing_santri_and_cleans_asterisk_marker(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $kelas8b = Kelas::create([
            'nama_kelas' => '8B',
            'wali_kelas' => '-',
            'kapasitas' => 0,
            'branch_id' => $school->id,
        ]);

        $santri = Santri::create([
            'nama' => 'Adiba Shakila Ramadhani',
            'kelas_id' => $kelas8b->id,
            'branch_id' => $school->id,
        ]);

        $filePath = $this->createImportFileWithAsterisk();

        $this->post(route('santri.import.process'), [
            'file' => new UploadedFile(
                $filePath,
                'Setor Tabungan.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])->assertSessionHas('importResult', function (array $result) {
            return $result['kelas_diperbaiki'] === 1
                && $result['created_santri'] === 1
                && $result['transactions_created'] === 2;
        });

        $kelas7c = Kelas::where('nama_kelas', '7C')->first();
        $this->assertNotNull($kelas7c);

        $this->assertDatabaseHas('santris', [
            'id' => $santri->id,
            'kelas_id' => $kelas7c->id,
        ]);

        $this->assertDatabaseCount('santris', 2);
    }

    protected function createImportFile(): string
    {
        return $this->writeWorkbook([
            ['name' => 'Adiba Shakila Ramadhani', 'kelas' => '7C', 'juni' => 20000, 'juli' => 30000, 'tarik_juli' => 10000],
            ['name' => 'Aira Kalila', 'kelas' => '7C', 'juni' => 0, 'juli' => 15000, 'tarik_juli' => 0],
        ]);
    }

    protected function createImportFileWithAsterisk(): string
    {
        return $this->writeWorkbook([
            ['name' => 'Adiba Shakila Ramadhani *', 'kelas' => '7C', 'juni' => 20000, 'juli' => 30000, 'tarik_juli' => 0],
            ['name' => 'Aira Kalila', 'kelas' => '7C', 'juni' => 0, 'juli' => 0, 'tarik_juli' => 0],
        ]);
    }

    protected function writeWorkbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Siswa');
        $sheet->setCellValue('C4', 'Nama');
        $sheet->setCellValue('D4', 'Kelas');
        $row = 5;
        foreach ($rows as $data) {
            $sheet->setCellValue('C'.$row, $data['name']);
            $sheet->setCellValue('D'.$row, $data['kelas']);
            $row++;
        }

        $this->writeMonthlySheet($spreadsheet, 'S-Juni', $rows, 'AI', 'juni', 0);
        $this->writeMonthlySheet($spreadsheet, 'S-Juli', $rows, 'AJ', 'juli', 0);
        $this->writeMonthlySheet($spreadsheet, 'P-Juni', $rows, 'AI', 'tarik_juni', 0);
        $this->writeMonthlySheet($spreadsheet, 'P-Juli', $rows, 'AJ', 'tarik_juli', 0);

        $path = storage_path('app/private/imports-test/test-import-'.uniqid().'.xlsx');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    protected function writeMonthlySheet($spreadsheet, string $title, array $rows, string $totalCol, string $key, $default): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $sheet->setCellValue('C4', 'Kelas');
        $sheet->setCellValue('D4', 'Nama');
        $sheet->setCellValue($totalCol.'4', 'Jumlah');
        $row = 5;
        foreach ($rows as $data) {
            $sheet->setCellValue('D'.$row, $data['name']);
            $sheet->setCellValue($totalCol.$row, $data[$key] ?? $default);
            $row++;
        }
    }
}
