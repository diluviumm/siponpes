<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\AccountCodes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SantriImportService
{
    protected array $monthMap = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function import(string $filePath, ?int $branchId = null): array
    {
        $branchId = $branchId ?? auth()->user()?->branch_id;

        if (! $branchId) {
            throw new \RuntimeException('Sekolah belum ditentukan untuk import data.');
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($filePath);

        $siswaSheet = $spreadsheet->getSheetByName('Siswa');

        if (! $siswaSheet) {
            $spreadsheet->disconnectWorksheets();

            throw new \RuntimeException('Sheet "Siswa" tidak ditemukan di file Excel. Pastikan file memiliki sheet bernama "Siswa".');
        }

        $students = $this->readStudentList($siswaSheet);

        // Baca semua sheet S-* dan P-* secara dinamis
        $savingsByMonth = [];
        $withdrawalsByMonth = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if (preg_match('/^S-(\w+)$/', $sheetName, $m)) {
                $monthName = $m[1];
                if (isset($this->monthMap[$monthName])) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if ($sheet) {
                        $savingsByMonth[$monthName] = $this->readMonthlySavings($sheet);
                    }
                }
            } elseif (preg_match('/^P-(\w+)$/', $sheetName, $m)) {
                $monthName = $m[1];
                if (isset($this->monthMap[$monthName])) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);
                    if ($sheet) {
                        $withdrawalsByMonth[$monthName] = $this->readMonthlySavings($sheet);
                    }
                }
            }
        }

        $spreadsheet->disconnectWorksheets();

        $stats = [
            'created_santri' => 0,
            'existing_santri' => 0,
            'kelas_diperbaiki' => 0,
            'created_kelas' => 0,
            'transactions_created' => 0,
            'total_amount' => 0,
            'total_penarikan' => 0,
            'errors' => [],
        ];

        if (count($students) === 0) {
            throw new \RuntimeException('Tidak ada data santri yang ditemukan di sheet "Siswa".');
        }

        // Sorted months (Januari..Desember)
        $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $year = Carbon::now()->year;

        DB::transaction(function () use ($students, $savingsByMonth, $withdrawalsByMonth, $bulanIndo, $year, $branchId, &$stats) {
            $existingCounts = Santri::where('branch_id', $branchId)
                ->selectRaw('kelas_id, COUNT(*) as total')
                ->groupBy('kelas_id')
                ->pluck('total', 'kelas_id')
                ->toArray();

            $addedPerKelas = [];

            foreach ($students as $student) {
                $nama = trim($student['nama'] ?? '');
                $namaKelas = trim($student['kelas'] ?? '');

                if ($nama === '') {
                    continue;
                }

                if ($namaKelas === '') {
                    $stats['errors'][] = "Santri \"{$nama}\" dilewati: nama kelas kosong.";

                    continue;
                }

                $kelas = Kelas::where('branch_id', $branchId)
                    ->where('nama_kelas', $namaKelas)
                    ->first();

                if (! $kelas) {
                    $kelas = Kelas::create([
                        'nama_kelas' => $namaKelas,
                        'wali_kelas' => '-',
                        'kapasitas' => 0,
                        'branch_id' => $branchId,
                    ]);
                    $stats['created_kelas']++;
                }

                $matches = Santri::where('branch_id', $branchId)->where('nama', $nama)->get();

                if ($matches->count() === 1) {
                    $santri = $matches->first();

                    if ($santri->kelas_id !== $kelas->id) {
                        if ($kelas->kapasitas > 0) {
                            $currentCount = ($existingCounts[$kelas->id] ?? 0) + ($addedPerKelas[$kelas->id] ?? 0);

                            if ($currentCount >= $kelas->kapasitas) {
                                $stats['errors'][] = "Santri \"{$nama}\" tidak dipindahkan ke \"{$namaKelas}\": kelas tujuan sudah penuh (kapasitas {$kelas->kapasitas}).";

                                continue;
                            }
                        }

                        $santri->update(['kelas_id' => $kelas->id]);
                        $stats['kelas_diperbaiki']++;
                    } else {
                        $stats['existing_santri']++;
                    }
                } elseif ($matches->count() > 1) {
                    $byKelas = $matches->firstWhere('kelas_id', $kelas->id);

                    if ($byKelas) {
                        $santri = $byKelas;
                        $stats['existing_santri']++;
                    } else {
                        if ($kelas->kapasitas > 0) {
                            $currentCount = ($existingCounts[$kelas->id] ?? 0) + ($addedPerKelas[$kelas->id] ?? 0);

                            if ($currentCount >= $kelas->kapasitas) {
                                $stats['errors'][] = "Santri \"{$nama}\" dilewati: kelas \"{$namaKelas}\" sudah penuh (kapasitas {$kelas->kapasitas}).";

                                continue;
                            }
                        }

                        $stats['errors'][] = "Nama \"{$nama}\" lebih dari satu di database; dibuat baru (periksa kembali).";
                        $santri = Santri::create([
                            'nama' => $nama,
                            'kelas_id' => $kelas->id,
                            'nis' => null,
                            'jenis_kelamin' => null,
                            'branch_id' => $branchId,
                        ]);
                        $stats['created_santri']++;
                        $addedPerKelas[$kelas->id] = ($addedPerKelas[$kelas->id] ?? 0) + 1;
                    }
                } else {
                    if ($kelas->kapasitas > 0) {
                        $currentCount = ($existingCounts[$kelas->id] ?? 0) + ($addedPerKelas[$kelas->id] ?? 0);

                        if ($currentCount >= $kelas->kapasitas) {
                            $stats['errors'][] = "Santri \"{$nama}\" dilewati: kelas \"{$namaKelas}\" sudah penuh (kapasitas {$kelas->kapasitas}).";

                            continue;
                        }
                    }

                    $santri = Santri::create([
                        'nama' => $nama,
                        'kelas_id' => $kelas->id,
                        'nis' => null,
                        'jenis_kelamin' => null,
                        'branch_id' => $branchId,
                    ]);
                    $stats['created_santri']++;
                    $addedPerKelas[$kelas->id] = ($addedPerKelas[$kelas->id] ?? 0) + 1;
                }

                $wallet = $santri->wallet()->firstOrCreate(
                    ['santri_id' => $santri->id],
                    ['balance' => 0, 'daily_spending_limit' => 0]
                );

                $balanceDelta = 0;

                foreach ($bulanIndo as $bulan) {
                    $monthNum = $this->monthMap[$bulan];
                    $date = Carbon::create($year, $monthNum, 1, 12, 0, 0)->endOfMonth();

                    $setor = (float) (($savingsByMonth[$bulan] ?? [])[$nama] ?? 0);
                    $tarik = (float) (($withdrawalsByMonth[$bulan] ?? [])[$nama] ?? 0);

                    if ($setor > 0 && ! $this->hasImportTransaction($wallet, $bulan, 'credit', 'Setoran')) {
                        $this->createTransaction($wallet, $setor, 'credit', 'Setoran tabungan bulan '.$bulan.' (import)', $date);
                        $balanceDelta += $setor;
                        $stats['transactions_created']++;
                        $stats['total_amount'] += $setor;
                    }

                    if ($tarik > 0 && ! $this->hasImportTransaction($wallet, $bulan, 'debit', 'Penarikan')) {
                        $this->createTransaction($wallet, $tarik, 'debit', 'Penarikan tabungan bulan '.$bulan.' (import)', $date);
                        $balanceDelta -= $tarik;
                        $stats['transactions_created']++;
                        $stats['total_penarikan'] += $tarik;
                    }
                }

                if ($balanceDelta !== 0) {
                    $wallet->update(['balance' => max(0, (float) $wallet->balance + $balanceDelta)]);
                }
            }

            $classCounts = Santri::where('branch_id', $branchId)
                ->selectRaw('kelas_id, COUNT(*) as total')
                ->groupBy('kelas_id')
                ->pluck('total', 'kelas_id');

            // Update kapasitas hanya untuk kelas baru yang kapasitasnya 0 (hasil create import)
            Kelas::where('branch_id', $branchId)->get()->each(function ($kelas) use ($classCounts) {
                $count = (int) $classCounts->get($kelas->id, 0);

                if ($kelas->kapasitas == 0 && $count > 0) {
                    $kelas->update(['kapasitas' => $count]);
                }
            });

            if ($stats['total_amount'] > 0) {
                app(JournalService::class)->createJournalEntry(
                    'Import setoran tabungan santri',
                    'IMPORT-S-'.now()->format('YmdHis'),
                    [
                        ['account_code' => AccountCodes::CASH, 'debit' => $stats['total_amount'], 'credit' => 0],
                        ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => 0, 'credit' => $stats['total_amount']],
                    ],
                    now()->toDateString(),
                    $branchId
                );
            }

            if ($stats['total_penarikan'] > 0) {
                app(JournalService::class)->createJournalEntry(
                    'Import penarikan tabungan santri',
                    'IMPORT-P-'.now()->format('YmdHis'),
                    [
                        ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $stats['total_penarikan'], 'credit' => 0],
                        ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => $stats['total_penarikan']],
                    ],
                    now()->toDateString(),
                    $branchId
                );
            }
        });

        return $stats;
    }

    protected function hasImportTransaction(Wallet $wallet, string $month, string $type, string $label): bool
    {
        return WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', $type)
            ->where('description', $label.' tabungan bulan '.$month.' (import)')
            ->exists();
    }

    protected function createTransaction(Wallet $wallet, float $amount, string $type, string $description, Carbon $date): void
    {
        $transaction = WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => $type,
            'description' => $description,
            'reference_id' => null,
        ]);

        $transaction->created_at = $date;
        $transaction->updated_at = $date;
        $transaction->save();
    }

    protected function readStudentList($sheet): array
    {
        $info = $this->findHeader($sheet, ['Nama', 'Kelas']);

        if (! $info) {
            throw new \RuntimeException('Header "Nama" dan "Kelas" tidak ditemukan di sheet "Siswa".');
        }

        $namaCol = $info['columns']['Nama'];
        $kelasCol = $info['columns']['Kelas'];
        $students = [];

        for ($row = $info['row'] + 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $nama = $this->cleanName($this->cellValue($sheet, $namaCol, $row));

            if ($nama === '') {
                continue;
            }

            $students[] = [
                'nama' => $nama,
                'kelas' => trim((string) ($this->cellValue($sheet, $kelasCol, $row) ?? '')),
            ];
        }

        return $students;
    }

    protected function readMonthlySavings($sheet): array
    {
        // Coba cari header "Jumlah" atau "JUMLAH SETORAN" / "JUMLAH PENARIKAN" dulu (format lama)
        $info = $this->findHeader($sheet, ['Nama', ['Jumlah', 'JUMLAH SETORAN', 'JUMLAH PENARIKAN']]);

        if ($info) {
            // Format lama: kolom Jumlah eksplisit
            $namaCol = $info['columns']['Nama'];
            $jumlahCol = $info['columns']['Jumlah'];
            $savings = [];

            for ($row = $info['row'] + 1; $row <= $sheet->getHighestDataRow(); $row++) {
                $nama = $this->cleanName($this->cellValue($sheet, $namaCol, $row));

                if ($nama === '') {
                    continue;
                }

                $savings[$nama] = (float) ($this->cellValue($sheet, $jumlahCol, $row) ?? 0);
            }

            return $savings;
        }

        // Format baru (template user): jumlahkan semua sel numerik di kolom setelah header
        $info2 = $this->findHeader($sheet, ['Nama']);

        if (! $info2) {
            throw new \RuntimeException('Header "Nama" tidak ditemukan di sheet "'.$sheet->getTitle().'".');
        }

        $namaCol = $info2['columns']['Nama'];
        $savings = [];
        $maxCol = $sheet->getHighestDataColumn();

        for ($row = $info2['row'] + 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $nama = $this->cleanName($this->cellValue($sheet, $namaCol, $row));

            if ($nama === '') {
                continue;
            }

            $total = 0;

            for ($col = $namaCol + 1; $col <= $this->colIndex($maxCol); $col++) {
                $value = $this->cellValue($sheet, $col, $row);

                if (is_numeric($value)) {
                    $total += (float) $value;
                }
            }

            $savings[$nama] = $total;
        }

        return $savings;
    }

    protected function colIndex(string $column): int
    {
        return Coordinate::columnIndexFromString($column);
    }

    protected function cleanName($value): string
    {
        $nama = trim((string) $value);
        $nama = preg_replace('/\s*\*\s*$/', '', $nama);

        return trim($nama);
    }

    protected function findHeader($sheet, array $requiredHeaders): ?array
    {
        for ($row = 1; $row <= 15; $row++) {
            $found = [];

            foreach ($requiredHeaders as $key => $header) {
                $candidates = is_array($header) ? $header : [$header];
                $label = is_numeric($key) ? (is_array($header) ? $candidates[0] : $header) : $key;

                $foundCol = null;
                foreach ($candidates as $candidate) {
                    for ($col = 1; $col <= 100; $col++) {
                        $value = $this->cellValue($sheet, $col, $row);

                        if ($value !== null && strcasecmp(trim((string) $value), $candidate) === 0) {
                            $foundCol = $col;
                            break 2;
                        }
                    }
                }

                if ($foundCol !== null) {
                    $found[$label] = $foundCol;
                }
            }

            if (count($found) === count($requiredHeaders)) {
                return ['row' => $row, 'columns' => $found];
            }
        }

        return null;
    }

    protected function cellValue($sheet, int $col, int $row)
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col).$row);
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            return $cell->getOldCalculatedValue();
        }

        return $value;
    }
}
