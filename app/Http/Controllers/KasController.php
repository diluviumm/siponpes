<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalDetail;
use App\Models\KasTransaction;
use App\Models\School;
use App\Services\JournalService;
use App\Services\PdfService;
use App\Support\AccountCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class KasController extends Controller
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

        $branchId = $school?->id;
        $type = $request->query('type');
        $from = $request->query('from');
        $to = $request->query('to');

        $query = KasTransaction::with('account')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($from, fn ($q) => $q->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('transaction_date', '<=', $to));

        $totalPemasukan = (clone $query)->where('type', 'pemasukan')->sum('amount');
        $totalPengeluaran = (clone $query)->where('type', 'pengeluaran')->sum('amount');
        $transactions = (clone $query)->orderByDesc('transaction_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $auditLogs = $transactions->isNotEmpty()
            ? AuditLog::where('auditable_type', KasTransaction::class)
                ->whereIn('auditable_id', $transactions->pluck('id'))
                ->with('user')
                ->orderBy('id')
                ->get()
                ->groupBy('auditable_id')
            : collect();

        $saldoKas = $this->cashBalanceAsOf($branchId, $to ?? now()->toDateString());
        $saldoNegatif = $saldoKas < 0;

        if ($request->query('pdf')) {
            $exportTransactions = (clone $query)->orderByDesc('transaction_date')->orderByDesc('id')->get();

            $title = 'Laporan Kas Sekolah';
            $subtitle = $school ? $title.' — '.$school->name : $title;

            return app(PdfService::class)->download(
                'kas.pdf.index',
                compact('exportTransactions', 'totalPemasukan', 'totalPengeluaran', 'saldoKas', 'title', 'subtitle'),
                'laporan_kas_'.now()->format('Ymd_His').'.pdf'
            );
        }

        if ($request->query('excel')) {
            $exportTransactions = (clone $query)->orderByDesc('transaction_date')->orderByDesc('id')->get();

            return $this->streamExcel(
                'laporan_kas_'.now()->format('Ymd_His').'.xlsx',
                $exportTransactions,
                (float) $totalPemasukan,
                (float) $totalPengeluaran,
                (float) $saldoKas
            );
        }

        return view('kas.index', compact(
            'school',
            'schools',
            'transactions',
            'totalPemasukan',
            'totalPengeluaran',
            'saldoKas',
            'saldoNegatif',
            'type',
            'from',
            'to',
            'auditLogs'
        ));
    }

    public function riwayat(Request $request)
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

        $branchId = $school?->id;

        $search = $request->query('search');

        $logs = AuditLog::with('user')
            ->where('auditable_type', KasTransaction::class)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, fn ($q) => $q->where('summary', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $kasById = collect();

        if ($logs->isNotEmpty()) {
            $kasById = KasTransaction::withoutGlobalScopes()
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('id', $logs->pluck('auditable_id')->filter())
                ->get()
                ->keyBy('id');
        }

        return view('kas.riwayat', compact('school', 'schools', 'logs', 'kasById', 'search'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $school = null;

        if ($user->isSuperadmin()) {
            $school = $request->query('school_id')
                ? School::findOrFail($request->query('school_id'))
                : null;

            if (! $school) {
                return redirect()->route('kas.index')
                    ->with('error', 'Pilih sekolah terlebih dahulu untuk mencatat transaksi kas.');
            }
        } else {
            $school = $user->school;
        }

        $revenueAccounts = Account::where('type', 'revenue')->orderBy('account_code')->get();
        $expenseAccounts = Account::where('type', 'expense')->orderBy('account_code')->get();
        $saldoTersedia = $this->cashBalanceAsOf($school->id, now()->toDateString());

        return view('kas.create', compact('school', 'revenueAccounts', 'expenseAccounts', 'saldoTersedia'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'type' => ['required', 'in:pemasukan,pengeluaran'],
            'transaction_date' => ['required', 'date'],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where(function ($query) use ($request) {
                $query->where('type', $request->input('type') === 'pemasukan' ? 'revenue' : 'expense');
            })],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($validated['account_id']);

        DB::transaction(function () use ($validated, $account, $branchId) {
            $this->lockCashAccount();

            $available = $this->cashBalanceAsOf($branchId, $validated['transaction_date']);

            if ($validated['type'] === 'pengeluaran' && (float) $validated['amount'] > $available) {
                throw ValidationException::withMessages([
                    'amount' => $this->insufficientCashMessage($available),
                ]);
            }

            $reference = 'KAS-'.now()->format('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            if ($validated['type'] === 'pemasukan') {
                $details = [
                    ['account_code' => AccountCodes::CASH, 'debit' => $validated['amount'], 'credit' => 0],
                    ['account_code' => $account->account_code, 'debit' => 0, 'credit' => $validated['amount']],
                ];
            } else {
                $details = [
                    ['account_code' => $account->account_code, 'debit' => $validated['amount'], 'credit' => 0],
                    ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => $validated['amount']],
                ];
            }

            $label = ($validated['type'] === 'pemasukan' ? 'Pemasukan kas: ' : 'Pengeluaran kas: ')
                .(($validated['description'] ?? null) ?: 'Transaksi kas');

            $journal = app(JournalService::class)->createJournalEntry(
                $label,
                $reference,
                $details,
                $validated['transaction_date'],
                $branchId
            );

            KasTransaction::create([
                'branch_id' => $branchId,
                'transaction_date' => $validated['transaction_date'],
                'type' => $validated['type'],
                'account_id' => $account->id,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'journal_id' => $journal->id,
            ]);
        });

        return redirect()->route('kas.index', $this->schoolQuery($branchId))
            ->with('success', 'Transaksi kas berhasil dicatat.');
    }

    public function edit(KasTransaction $kasTransaction)
    {
        $school = $kasTransaction->school;

        $revenueAccounts = Account::where('type', 'revenue')->orderBy('account_code')->get();
        $expenseAccounts = Account::where('type', 'expense')->orderBy('account_code')->get();
        $saldoTersedia = $this->cashBalanceAsOf($kasTransaction->branch_id, now()->toDateString(), $kasTransaction->journal_id);

        return view('kas.edit', compact('kasTransaction', 'school', 'revenueAccounts', 'expenseAccounts', 'saldoTersedia'));
    }

    public function update(Request $request, KasTransaction $kasTransaction)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:pemasukan,pengeluaran'],
            'transaction_date' => ['required', 'date'],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where(function ($query) use ($request) {
                $query->where('type', $request->input('type') === 'pemasukan' ? 'revenue' : 'expense');
            })],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $account = Account::findOrFail($validated['account_id']);

        DB::transaction(function () use ($validated, $account, $kasTransaction) {
            $this->lockCashAccount();

            $available = $this->cashBalanceAsOf(
                $kasTransaction->branch_id,
                $validated['transaction_date'],
                $kasTransaction->journal_id
            );

            $newContribution = $validated['type'] === 'pemasukan'
                ? (float) $validated['amount']
                : -(float) $validated['amount'];

            if ($available + $newContribution < 0) {
                throw ValidationException::withMessages([
                    'amount' => $this->insufficientCashMessage($available),
                ]);
            }

            if ($kasTransaction->journal_id) {
                $kasTransaction->journal?->delete();
            }

            $reference = 'KAS-'.now()->format('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            if ($validated['type'] === 'pemasukan') {
                $details = [
                    ['account_code' => AccountCodes::CASH, 'debit' => $validated['amount'], 'credit' => 0],
                    ['account_code' => $account->account_code, 'debit' => 0, 'credit' => $validated['amount']],
                ];
            } else {
                $details = [
                    ['account_code' => $account->account_code, 'debit' => $validated['amount'], 'credit' => 0],
                    ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => $validated['amount']],
                ];
            }

            $label = ($validated['type'] === 'pemasukan' ? 'Pemasukan kas: ' : 'Pengeluaran kas: ')
                .(($validated['description'] ?? null) ?: 'Transaksi kas');

            $journal = app(JournalService::class)->createJournalEntry(
                $label,
                $reference,
                $details,
                $validated['transaction_date'],
                $kasTransaction->branch_id
            );

            $kasTransaction->update([
                'transaction_date' => $validated['transaction_date'],
                'type' => $validated['type'],
                'account_id' => $account->id,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'journal_id' => $journal->id,
            ]);
        });

        return redirect()->route('kas.index', $this->schoolQuery($kasTransaction->branch_id))
            ->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(KasTransaction $kasTransaction)
    {
        DB::transaction(function () use ($kasTransaction) {
            $this->lockCashAccount();

            if ($kasTransaction->type === 'pemasukan') {
                $minBalance = $this->cashRunningMinimum(
                    $kasTransaction->branch_id,
                    $kasTransaction->journal_id
                );

                if ($minBalance < 0) {
                    throw ValidationException::withMessages([
                        'id' => 'Penghapusan transaksi pemasukan ini akan membuat saldo kas negatif pada satu atau lebih periode. Tidak dapat dihapus.',
                    ]);
                }
            }

            if ($kasTransaction->journal_id) {
                $kasTransaction->journal?->delete();
            }

            $kasTransaction->delete();
        });

        return redirect()->route('kas.index', $this->schoolQuery($kasTransaction->branch_id))
            ->with('success', 'Transaksi kas berhasil dihapus.');
    }

    protected function cashBalanceAsOf(?int $branchId, ?string $asOf = null, ?int $excludeJournalId = null): float
    {
        $cashAccountId = Account::where('account_code', AccountCodes::CASH)->value('id');

        if (! $cashAccountId) {
            return 0;
        }

        $totals = JournalDetail::where('account_id', $cashAccountId)
            ->when($excludeJournalId, fn ($q) => $q->where('journal_id', '!=', $excludeJournalId))
            ->whereHas('journal', function ($q) use ($branchId, $asOf) {
                $q->when($branchId, fn ($qq) => $qq->where('branch_id', $branchId))
                    ->when($asOf, fn ($qq) => $qq->whereDate('transaction_date', '<=', $asOf));
            })
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return (float) (($totals->total_debit ?? 0) - ($totals->total_credit ?? 0));
    }

    protected function cashRunningMinimum(?int $branchId, ?int $excludeJournalId = null): float
    {
        $cashAccountId = Account::where('account_code', AccountCodes::CASH)->value('id');

        if (! $cashAccountId) {
            return 0;
        }

        $details = JournalDetail::select('journal_details.debit', 'journal_details.credit')
            ->join('journals', 'journals.id', '=', 'journal_details.journal_id')
            ->where('journal_details.account_id', $cashAccountId)
            ->when($branchId, fn ($q) => $q->where('journals.branch_id', $branchId))
            ->when($excludeJournalId, fn ($q) => $q->where('journal_details.journal_id', '!=', $excludeJournalId))
            ->orderBy('journals.transaction_date')
            ->orderBy('journals.id')
            ->get();

        $running = 0;
        $minimum = 0;

        foreach ($details as $detail) {
            $running += (float) $detail->debit - (float) $detail->credit;

            if ($running < $minimum) {
                $minimum = $running;
            }
        }

        return $minimum;
    }

    protected function lockCashAccount(): void
    {
        Account::where('account_code', AccountCodes::CASH)->lockForUpdate()->first();
    }

    protected function insufficientCashMessage(float $available): string
    {
        return 'Saldo kas tidak mencukupi. Saldo tersedia Rp'.number_format($available, 0, ',', '.').'.';
    }

    protected function streamExcel(string $filename, $transactions, float $totalPemasukan, float $totalPengeluaran, float $saldoKas)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kas Sekolah');

        $headers = ['Tanggal', 'Jenis', 'Akun', 'Keterangan', 'Jumlah'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF2F0EA');

        $rowIndex = 2;

        foreach ($transactions as $kas) {
            $sheet->setCellValueExplicit('A'.$rowIndex, $kas->transaction_date->format('d/m/Y'), DataType::TYPE_STRING);
            $sheet->setCellValue('B'.$rowIndex, ucfirst($kas->type));
            $sheet->setCellValue('C'.$rowIndex, ($kas->account?->account_code ?? '-').' - '.($kas->account?->account_name ?? '-'));
            $sheet->setCellValue('D'.$rowIndex, $kas->description ?? '-');
            $sheet->setCellValue('E'.$rowIndex, (float) $kas->amount);
            $rowIndex++;
        }

        $rowIndex++;

        $sheet->setCellValue('D'.$rowIndex, 'Total Pemasukan');
        $sheet->setCellValue('E'.$rowIndex, $totalPemasukan);
        $rowIndex++;

        $sheet->setCellValue('D'.$rowIndex, 'Total Pengeluaran');
        $sheet->setCellValue('E'.$rowIndex, $totalPengeluaran);
        $rowIndex++;

        $sheet->setCellValue('D'.$rowIndex, 'Saldo Kas');
        $sheet->setCellValue('E'.$rowIndex, $saldoKas);

        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'siponpes_');
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
