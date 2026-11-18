<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Canteen;
use App\Models\CanteenItem;
use App\Models\CanteenTransactionItem;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Services\CanteenService;
use App\Services\PdfService;
use App\Support\CanteenUnits;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CanteenController extends Controller
{
    use HandlesSchoolContext;

    public function index(Request $request)
    {
        $user = auth()->user();
        $school = null;
        $schools = collect();

        if ($user->isSuperadmin()) {
            $schoolId = $request->query('school_id');
            $school = $schoolId ? School::findOrFail($schoolId) : null;
            $schools = School::where('status', School::STATUS_ACTIVE)
                ->withCount('santris')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $search = $request->query('search');
        $kelasId = $request->query('kelas_id');
        $santris = collect();
        $canteens = collect();
        $menuItems = collect();
        $kelasList = collect();

        if ($school) {
            $kelasList = Kelas::where('branch_id', $school->id)
                ->orderBy('nama_kelas')
                ->get();

            $santris = Santri::with('wallet', 'kelas')
                ->where('branch_id', $school->id)
                ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%")
                            ->orWhere('santri_card_token', 'like', "%{$search}%");
                    });
                })
                ->get();

            $canteens = Canteen::where('branch_id', $school->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $menuItems = CanteenItem::whereIn('canteen_id', $canteens->pluck('id'))
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->get()
                ->groupBy('canteen_id');
        }

        return view('kantin.index', compact('school', 'schools', 'santris', 'search', 'kelasId', 'kelasList', 'canteens', 'menuItems'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'santri_id' => ['required', Rule::exists('santris', 'id')->where(function ($query) use ($user) {
                if (! $user->isSuperadmin()) {
                    $query->where('branch_id', $user->branch_id);
                }
            })],
            'canteen_id' => ['nullable', Rule::exists('canteens', 'id')->where(function ($query) {
                $query->where('is_active', true);
            })],
            'canteen_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'in:wallet,cash'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', Rule::in(CanteenUnits::all())],
            'items.*.canteen_item_id' => ['nullable', function ($attribute, $value, $fail) use ($request) {
                $value = $value === '' || $value === null ? null : $value;

                if ($request->input('canteen_id')) {
                    if ($value !== null && ! CanteenItem::where('id', $value)
                        ->where('canteen_id', $request->input('canteen_id'))
                        ->exists()) {
                        $fail('Item terpilih bukan bagian dari kantin yang dipilih.');
                    }
                } elseif ($value !== null) {
                    $fail('Item terdata hanya bisa digunakan saat memilih kantin.');
                }
            }],
        ]);

        if (! empty($validated['items'])) {
            $sumItems = collect($validated['items'])->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_price']);

            if (abs($sumItems - (float) $validated['amount']) > 0.01) {
                return redirect()->back()->withInput()
                    ->withErrors(['amount' => 'Jumlah (Rp) harus sama dengan total harga item di keranjang.']);
            }
        }

        $santri = Santri::findOrFail($validated['santri_id']);
        $branchId = $santri->branch_id;

        $canteenId = $validated['canteen_id'] ?? null;

        if ($canteenId) {
            $canteen = Canteen::where('branch_id', $branchId)->find($canteenId);

            if (! $canteen) {
                return redirect()->back()->with('error', 'Kantin tidak ditemukan untuk sekolah santri.');
            }

            $canteenName = $canteen->name;
        } else {
            $canteenName = $validated['canteen_name'] ?? null;

            if (! $canteenName) {
                return redirect()->back()->withInput()->withErrors(['canteen_name' => 'Pilih kantin atau isi nama kantin.']);
            }
        }

        $paymentMethod = $validated['payment_method'] ?? 'wallet';

        $result = app(CanteenService::class)->purchase(
            $santri,
            (float) $validated['amount'],
            $canteenName,
            $branchId,
            $validated['items'] ?? [],
            $canteenId,
            $paymentMethod
        );

        if ($result['status'] === 'insufficient_stock') {
            return redirect()->back()->with('error', "Stok '{$result['item']}' tidak mencukupi untuk jumlah yang diminta.");
        }

        if ($result['status'] === 'wallet_missing') {
            return redirect()->back()->with('error', 'Wallet santri belum ada. Lakukan top-up terlebih dahulu.');
        }

        if ($result['status'] === 'insufficient_balance') {
            $balance = $result['balance'] ?? 0;

            return redirect()->back()->with('error', 'Saldo wallet santri tidak mencukupi. Saldo tersedia: Rp '.number_format($balance, 2, ',', '.').'.');
        }

        if ($result['status'] === 'daily_limit_exceeded') {
            $remaining = $result['remaining_limit'] ?? 0;

            return redirect()->back()->with('error', 'Transaksi melebihi batas pengeluaran harian santri. Sisa limit hari ini: Rp '.number_format($remaining, 2, ',', '.').'.');
        }

        $methodLabel = $paymentMethod === 'cash' ? ' (tunai)' : ' (saldo)';

        return redirect()->back()->with('success', "Pembelian di {$canteenName}{$methodLabel} berhasil dicatat.");
    }

    public function rekap(Request $request)
    {
        $user = auth()->user();
        $school = null;
        $schools = collect();

        if ($user->isSuperadmin()) {
            $schoolId = $request->query('school_id');
            $school = $schoolId ? School::findOrFail($schoolId) : null;
            $schools = School::where('status', School::STATUS_ACTIVE)
                ->withCount('canteens')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $from = $request->query('from');
        $to = $request->query('to');

        $rows = collect();
        $canteenNames = collect();

        if ($school) {
            $canteenNames = Canteen::withTrashed()->where('branch_id', $school->id)->pluck('name', 'id');

            $rows = CanteenTransactionItem::join('canteen_transactions', 'canteen_transactions.id', '=', 'canteen_transaction_items.canteen_transaction_id')
                ->where('canteen_transactions.branch_id', $school->id)
                ->when($from, fn ($q) => $q->whereDate('canteen_transactions.transaction_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('canteen_transactions.transaction_date', '<=', $to))
                ->selectRaw('canteen_transactions.canteen_id, canteen_transactions.payment_method, canteen_transaction_items.item_name, canteen_transaction_items.unit, SUM(canteen_transaction_items.quantity) as total_qty, SUM(canteen_transaction_items.total) as total_revenue, COUNT(*) as tx_count')
                ->groupBy('canteen_transactions.canteen_id', 'canteen_transactions.payment_method', 'canteen_transaction_items.item_name', 'canteen_transaction_items.unit')
                ->orderBy('canteen_transaction_items.item_name')
                ->get();
        }

        $totalWallet = $rows->where('payment_method', 'wallet')->sum('total_revenue');
        $totalCash = $rows->where('payment_method', 'cash')->sum('total_revenue');
        $totalRevenue = $rows->sum('total_revenue');

        if ($request->query('pdf')) {
            $title = 'Rekap Penjualan Kantin';
            $subtitle = $school ? $title.' — '.$school->name : $title;

            return app(PdfService::class)->download(
                'kantin.pdf.rekap',
                compact('rows', 'canteenNames', 'totalWallet', 'totalCash', 'totalRevenue', 'title', 'subtitle'),
                'rekap_penjualan_'.now()->format('Ymd_His').'.pdf'
            );
        }

        if ($request->query('excel')) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rekap Penjualan');

            $headers = ['Kantin', 'Metode', 'Menu', 'Satuan', 'Jumlah Terjual', 'Transaksi', 'Pendapatan'];
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);
            $sheet->getStyle('A1:G1')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF2F0EA');

            $rowIndex = 2;

            foreach ($rows as $row) {
                $sheet->fromArray([
                    $canteenNames[$row->canteen_id] ?? 'Tanpa Kantin',
                    $row->payment_method === 'cash' ? 'Tunai' : 'Saldo (Wallet)',
                    $row->item_name,
                    $row->unit,
                    (float) $row->total_qty,
                    (int) $row->tx_count,
                    (float) $row->total_revenue,
                ], null, 'A'.$rowIndex);
                $rowIndex++;
            }

            $sheet->setCellValue('F'.$rowIndex, 'Total Saldo');
            $sheet->setCellValue('G'.$rowIndex, (float) $totalWallet);
            $rowIndex++;
            $sheet->setCellValue('F'.$rowIndex, 'Total Tunai');
            $sheet->setCellValue('G'.$rowIndex, (float) $totalCash);
            $rowIndex++;
            $sheet->setCellValue('F'.$rowIndex, 'Total Keseluruhan');
            $sheet->setCellValue('G'.$rowIndex, (float) $totalRevenue);

            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $tempFile = tempnam(sys_get_temp_dir(), 'siponpes_');
            $writer->save($tempFile);
            $spreadsheet->disconnectWorksheets();

            return response()->download($tempFile, 'rekap_penjualan_'.now()->format('Ymd_His').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return view('kantin.rekap', compact('school', 'schools', 'rows', 'canteenNames', 'totalWallet', 'totalCash', 'totalRevenue', 'from', 'to'));
    }
}
