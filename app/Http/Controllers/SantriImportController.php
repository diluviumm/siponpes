<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SantriImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SantriImportController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        $school = null;

        if ($user->isSuperadmin()) {
            $school = $request->query('school_id')
                ? School::findOrFail($request->query('school_id'))
                : null;

            if (! $school) {
                return redirect()->route('santri.index')
                    ->with('error', 'Pilih sekolah terlebih dahulu untuk import data.');
            }
        } else {
            $school = $user->school;
        }

        return view('santri.import', compact('school'));
    }

    public function store(Request $request)
    {
        set_time_limit(300);

        $user = auth()->user();
        $school = null;

        if ($user->isSuperadmin()) {
            if (! $request->query('school_id')) {
                return redirect()->route('santri.import')
                    ->with('error', 'Pilih sekolah terlebih dahulu untuk import data.');
            }

            $school = School::findOrFail($request->query('school_id'));
        } else {
            $school = $user->school;
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $schoolQuery = $user->isSuperadmin() ? ['school_id' => $school->id] : [];

        $path = $request->file('file')->store('imports');

        try {
            $result = app(SantriImportService::class)->import(Storage::path($path), $school->id);
        } catch (\Throwable $exception) {
            Storage::delete($path);

            return redirect()->route('santri.import', $schoolQuery)
                ->withErrors(['file' => 'Gagal membaca file: '.$exception->getMessage()]);
        }

        Storage::delete($path);

        return redirect()->route('santri.import', $schoolQuery)
            ->with('importResult', $result);
    }

    public function template()
    {
        $templatePath = base_path('docs/template.xlsx');

        if (file_exists($templatePath)) {
            return response()->download($templatePath, 'template-import-santri.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        // Fallback: generate template bawaan
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $menu = $spreadsheet->createSheet();
        $menu->setTitle('Menu');
        $menu->setCellValue('E3', 'TABUNGAN SISWA');
        $menu->setCellValue('E5', 'KLIK MENU DI BAWAH UNTUK INPUT DATA');
        $menu->setCellValue('E6', 'SETORAN');
        $menu->setCellValue('E7', 'PENARIKAN');
        $menu->setCellValue('E8', 'SALDO AKHIR');

        $siswa = $spreadsheet->createSheet();
        $siswa->setTitle('Siswa');
        $siswa->setCellValue('B4', 'No');
        $siswa->setCellValue('C4', 'Nama');
        $siswa->setCellValue('D4', 'Kelas');

        $saldo = $spreadsheet->createSheet();
        $saldo->setTitle('Saldo Akhir');
        $saldo->setCellValue('B4', 'No');
        $saldo->setCellValue('C4', 'Kelas');
        $saldo->setCellValue('D4', 'Nama');
        $saldo->setCellValue('J4', 'Saldo Akhir');

        $penarikan = $spreadsheet->createSheet();
        $penarikan->setTitle('Penarikan');
        $penarikan->setCellValue('B4', 'No');
        $penarikan->setCellValue('C4', 'Kelas');
        $penarikan->setCellValue('D4', 'Nama');
        $penarikan->setCellValue('AC4', 'Jumlah');

        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        foreach ($months as $month) {
            $sSheet = $spreadsheet->createSheet();
            $sSheet->setTitle('S-'.$month);
            $sSheet->setCellValue('B4', 'No');
            $sSheet->setCellValue('C4', 'Kelas');
            $sSheet->setCellValue('D4', 'Nama');
            $sSheet->setCellValue('AI4', 'Jumlah');

            $pSheet = $spreadsheet->createSheet();
            $pSheet->setTitle('P-'.$month);
            $pSheet->setCellValue('B4', 'No');
            $pSheet->setCellValue('C4', 'Kelas');
            $pSheet->setCellValue('D4', 'Nama');
            $pSheet->setCellValue('AI4', 'Jumlah');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $tempFile = sys_get_temp_dir().'/template-import-santri-'.uniqid().'.xlsx';
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, 'template-import-santri.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
