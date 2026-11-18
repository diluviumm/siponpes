<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\School;
use App\Services\PdfService;
use App\Support\AccountCodes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FinancialReportController extends Controller
{
    public function generalJournal(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $journals = Journal::with('details.account')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($period['from'], fn ($q) => $q->whereDate('transaction_date', '>=', $period['from']))
            ->when($period['to'], fn ($q) => $q->whereDate('transaction_date', '<=', $period['to']))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.general_journal', compact('journals'), 'Jurnal Umum', 'jurnal-umum', $branchId, $period);
        }

        if ($request->query('excel')) {
            $headers = ['Tanggal', 'Deskripsi', 'Referensi', 'Akun', 'Debit', 'Kredit'];
            $rows = [];

            foreach ($journals as $journal) {
                foreach ($journal->details as $detail) {
                    $rows[] = [
                        $journal->transaction_date->format('d/m/Y'),
                        $journal->description,
                        $journal->reference,
                        ($detail->account?->account_code ?? '-').' - '.($detail->account?->account_name ?? 'Akun dihapus'),
                        $detail->debit ? (float) $detail->debit : null,
                        $detail->credit ? (float) $detail->credit : null,
                    ];
                }
            }

            return $this->streamExcel('jurnal_umum_'.now()->format('Ymd_His').'.xlsx', $headers, $rows, 'Jurnal Umum');
        }

        return view('financial.general_journal', $this->withFilterData(compact('journals'), $request, $branchId));
    }

    public function ledger(Request $request)
    {
        $accountId = $request->query('account_id');
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $accounts = Account::orderBy('account_code')->get();

        $selectedAccount = null;
        $entries = collect();
        $balance = 0;
        $openingBalance = 0;
        $accountsSummary = collect();

        if ($accountId) {
            $selectedAccount = Account::find($accountId);

            if ($selectedAccount) {
                $entries = JournalDetail::with('journal')
                    ->where('account_id', $selectedAccount->id)
                    ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
                    ->when($period['from'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '>=', $period['from'])))
                    ->when($period['to'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<=', $period['to'])))
                    ->get()
                    ->sortBy(fn ($entry) => [$entry->journal->transaction_date ?? $entry->created_at, $entry->journal_id]);

                $openingBalance = 0;

                if ($period['from'] ?? null) {
                    $openingEntries = JournalDetail::with('journal')
                        ->where('account_id', $selectedAccount->id)
                        ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
                        ->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<', $period['from']))
                        ->get();

                    $openingBalance = $openingEntries->reduce(function ($carry, $entry) use ($selectedAccount) {
                        if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                            return $carry + $entry->debit - $entry->credit;
                        }

                        return $carry + $entry->credit - $entry->debit;
                    }, 0);
                }

                $balance = $openingBalance + $entries->reduce(function ($carry, $entry) use ($selectedAccount) {
                    if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                        return $carry + $entry->debit - $entry->credit;
                    }

                    return $carry + $entry->credit - $entry->debit;
                }, 0);
            }
        } else {
            $accountsSummary = $this->ledgerSummary($branchId, $period);
        }

        $viewData = compact('accounts', 'selectedAccount', 'entries', 'balance', 'openingBalance', 'accountsSummary');

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.ledger', $viewData, 'Buku Besar', 'buku-besar', $branchId, $period);
        }

        if ($request->query('excel')) {
            if ($selectedAccount) {
                $headers = ['Tanggal', 'Deskripsi', 'Referensi', 'Debit', 'Kredit', 'Saldo'];
                $rows = [];
                $running = 0;

                if ($openingBalance != 0) {
                    $rows[] = ['Saldo Awal', 'Saldo awal sebelum periode', '-', null, null, (float) $openingBalance];
                    $running = $openingBalance;
                }

                foreach ($entries as $entry) {
                    if (in_array($selectedAccount->type, ['asset', 'expense'])) {
                        $running += $entry->debit - $entry->credit;
                    } else {
                        $running += $entry->credit - $entry->debit;
                    }

                    $rows[] = [
                        $entry->journal?->transaction_date?->format('d/m/Y') ?? '-',
                        $entry->journal?->description ?? '-',
                        $entry->journal?->reference ?? '-',
                        $entry->debit ? (float) $entry->debit : null,
                        $entry->credit ? (float) $entry->credit : null,
                        (float) $running,
                    ];
                }
            } else {
                $headers = ['Kode', 'Akun', 'Tipe', 'Total Debit', 'Total Kredit', 'Saldo Akhir'];
                $rows = $accountsSummary->map(fn ($row) => [
                    $row['account']->account_code,
                    $row['account']->account_name,
                    ucfirst($row['account']->type),
                    $row['debit'] ? (float) $row['debit'] : null,
                    $row['credit'] ? (float) $row['credit'] : null,
                    (float) $row['balance'],
                ])->all();
            }

            return $this->streamExcel('buku_besar_'.now()->format('Ymd_His').'.xlsx', $headers, $rows, 'Buku Besar');
        }

        return view('financial.ledger', $this->withFilterData($viewData, $request, $branchId));
    }

    protected function ledgerSummary(?int $branchId, array $period = [])
    {
        return Account::with(['journalDetails' => function ($query) use ($branchId, $period) {
            $query->with('journal')
                ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
                ->when($period['from'] ?? null, fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '>=', $period['from'])))
                ->when($period['to'] ?? null, fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<=', $period['to'])));
        }])->orderBy('account_code')->get()->map(function ($account) {
            $debit = $account->journalDetails->sum('debit');
            $credit = $account->journalDetails->sum('credit');
            $balance = in_array($account->type, ['asset', 'expense']) ? $debit - $credit : $credit - $debit;

            return [
                'account' => $account,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        });
    }

    public function trialBalance(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $accounts = Account::with(['journalDetails' => function ($query) use ($branchId, $period) {
            $query->with('journal')
                ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
                ->when($period['from'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '>=', $period['from'])))
                ->when($period['to'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<=', $period['to'])));
        }])->orderBy('account_code')->get();

        $rows = $accounts->map(function ($account) {
            $debit = $account->journalDetails->sum('debit');
            $credit = $account->journalDetails->sum('credit');

            if (in_array($account->type, ['asset', 'expense'])) {
                $balance = $debit - $credit;

                return ['account' => $account, 'debit' => $balance, 'credit' => 0];
            }

            $balance = $credit - $debit;

            return ['account' => $account, 'debit' => 0, 'credit' => $balance];
        });

        $totalDebit = $rows->sum('debit');
        $totalCredit = $rows->sum('credit');

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.trial_balance', compact('rows', 'totalDebit', 'totalCredit'), 'Neraca Saldo', 'neraca-saldo', $branchId, $period);
        }

        if ($request->query('excel')) {
            $headers = ['Akun', 'Debit', 'Kredit'];
            $data = $rows->map(fn ($row) => [
                $row['account']->account_code.' - '.$row['account']->account_name,
                $row['debit'] ? (float) $row['debit'] : null,
                $row['credit'] ? (float) $row['credit'] : null,
            ])->all();

            $data[] = ['Total', (float) $totalDebit, (float) $totalCredit];

            return $this->streamExcel('neraca_saldo_'.now()->format('Ymd_His').'.xlsx', $headers, $data, 'Neraca Saldo');
        }

        return view('financial.trial_balance', $this->withFilterData(compact('rows', 'totalDebit', 'totalCredit'), $request, $branchId));
    }

    public function balanceSheet(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $assets = Account::where('type', 'asset')->get();
        $liabilities = Account::where('type', 'liability')->get();
        $equity = Account::where('type', 'equity')->get();
        $revenues = Account::where('type', 'revenue')->get();
        $expenses = Account::where('type', 'expense')->get();

        $assetsTotals = $this->accountBalances($assets, $branchId, $period);
        $liabilitiesTotals = $this->accountBalances($liabilities, $branchId, $period);
        $equityTotals = $this->accountBalances($equity, $branchId, $period);
        $revenueTotals = $this->accountBalances($revenues, $branchId, $period);
        $expenseTotals = $this->accountBalances($expenses, $branchId, $period);

        $totalAssets = $assetsTotals->sum('balance');
        $totalLiabilities = $liabilitiesTotals->sum('balance');
        $equityAccountsTotal = $equityTotals->sum('balance');
        $netIncome = $revenueTotals->sum('balance') - $expenseTotals->sum('balance');
        $totalEquity = $equityAccountsTotal + $netIncome;

        $viewData = compact(
            'assetsTotals',
            'liabilitiesTotals',
            'equityTotals',
            'equityAccountsTotal',
            'netIncome',
            'totalAssets',
            'totalLiabilities',
            'totalEquity'
        );

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.balance_sheet', $viewData, 'Laporan Neraca', 'neraca', $branchId, $period);
        }

        if ($request->query('excel')) {
            $headers = ['Keterangan', 'Jumlah'];
            $rows = [];

            $rows[] = ['ASET', ''];
            foreach ($assetsTotals as $item) {
                $rows[] = [$item->account->account_code.' - '.$item->account->account_name, (float) $item->balance];
            }
            $rows[] = ['Total Aset', (float) $totalAssets];
            $rows[] = ['', ''];

            $rows[] = ['KEWAJIBAN', ''];
            foreach ($liabilitiesTotals as $item) {
                $rows[] = [$item->account->account_code.' - '.$item->account->account_name, (float) $item->balance];
            }
            $rows[] = ['Total Kewajiban', (float) $totalLiabilities];
            $rows[] = ['', ''];

            $rows[] = ['EKUITAS', ''];
            foreach ($equityTotals as $item) {
                $rows[] = [$item->account->account_code.' - '.$item->account->account_name, (float) $item->balance];
            }
            $rows[] = ['Saldo Laba Berjalan (Surplus/Defisit)', (float) $netIncome];
            $rows[] = ['Total Ekuitas', (float) $totalEquity];
            $rows[] = ['', ''];

            $rows[] = ['Total Kewajiban & Ekuitas', (float) ($totalLiabilities + $totalEquity)];

            return $this->streamExcel('neraca_'.now()->format('Ymd_His').'.xlsx', $headers, $rows, 'Laporan Neraca');
        }

        return view('financial.balance_sheet', $this->withFilterData($viewData, $request, $branchId));
    }

    public function incomeStatement(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $revenues = Account::where('type', 'revenue')->get();
        $expenses = Account::where('type', 'expense')->get();

        $revenueTotals = $this->accountBalances($revenues, $branchId, $period);
        $expenseTotals = $this->accountBalances($expenses, $branchId, $period);

        $totalRevenue = $revenueTotals->sum('balance');
        $totalExpense = $expenseTotals->sum('balance');
        $netIncome = $totalRevenue - $totalExpense;

        $viewData = compact('revenueTotals', 'expenseTotals', 'totalRevenue', 'totalExpense', 'netIncome');

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.income_statement', $viewData, 'Laporan Surplus/Defisit', 'laba-rugi', $branchId, $period);
        }

        if ($request->query('excel')) {
            $headers = ['Keterangan', 'Jumlah'];
            $rows = [];

            $rows[] = ['PENDAPATAN', ''];
            foreach ($revenueTotals as $item) {
                $rows[] = [$item->account->account_code.' - '.$item->account->account_name, (float) $item->balance];
            }
            $rows[] = ['Total Pendapatan', (float) $totalRevenue];
            $rows[] = ['', ''];

            $rows[] = ['BEBAN', ''];
            foreach ($expenseTotals as $item) {
                $rows[] = [$item->account->account_code.' - '.$item->account->account_name, (float) $item->balance];
            }
            $rows[] = ['Total Beban', (float) $totalExpense];
            $rows[] = ['', ''];

            $rows[] = ['SURPLUS (DEFISIT)', (float) $netIncome];

            return $this->streamExcel('laba_rugi_'.now()->format('Ymd_His').'.xlsx', $headers, $rows, 'Surplus Defisit');
        }

        return view('financial.income_statement', $this->withFilterData($viewData, $request, $branchId));
    }

    public function cashFlow(Request $request)
    {
        $branchId = $this->resolveBranchFilter($request);
        $period = $this->resolvePeriod($request);

        $cashAccountId = Account::where('account_code', AccountCodes::CASH)->value('id');

        $details = JournalDetail::with('journal')
            ->where('account_id', $cashAccountId)
            ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
            ->when($period['from'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '>=', $period['from'])))
            ->when($period['to'], fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<=', $period['to'])))
            ->get()
            ->map(fn ($detail) => (object) [
                'date' => $detail->journal?->transaction_date,
                'description' => $detail->journal?->description ?? '-',
                'reference' => $detail->journal?->reference ?? '-',
                'debit' => (float) $detail->debit,
                'credit' => (float) $detail->credit,
                'category' => $this->cashFlowCategory($detail->journal?->description ?? ''),
            ])
            ->sortBy(fn ($d) => [$d->date, $d->description])
            ->values();

        $inflows = $details->where('debit', '>', 0);
        $outflows = $details->where('credit', '>', 0);

        $totalInflow = (float) $inflows->sum('debit');
        $totalOutflow = (float) $outflows->sum('credit');
        $netCash = $totalInflow - $totalOutflow;

        $inflowByCategory = $inflows->groupBy('category')->map(fn ($group) => (float) $group->sum('debit'))->sortKeys();
        $outflowByCategory = $outflows->groupBy('category')->map(fn ($group) => (float) $group->sum('credit'))->sortKeys();

        $viewData = compact('details', 'totalInflow', 'totalOutflow', 'netCash', 'inflowByCategory', 'outflowByCategory');

        if ($request->query('pdf')) {
            return $this->streamPdf($request, 'financial.pdf.cash_flow', $viewData, 'Laporan Arus Kas', 'arus-kas', $branchId, $period);
        }

        if ($request->query('excel')) {
            $headers = ['Tanggal', 'Deskripsi', 'Referensi', 'Kategori', 'Penerimaan', 'Pengeluaran'];
            $rows = $details->map(fn ($d) => [
                $d->date?->format('d/m/Y') ?? '-',
                $d->description,
                $d->reference,
                $d->category,
                $d->debit ? (float) $d->debit : null,
                $d->credit ? (float) $d->credit : null,
            ])->all();

            $rows[] = ['', '', '', 'TOTAL', (float) $totalInflow, (float) $totalOutflow];

            return $this->streamExcel('arus_kas_'.now()->format('Ymd_His').'.xlsx', $headers, $rows, 'Arus Kas');
        }

        return view('financial.cash_flow', $this->withFilterData($viewData, $request, $branchId));
    }

    protected function cashFlowCategory(string $description): string
    {
        $lower = strtolower($description);

        if (str_contains($lower, 'spp')) {
            return 'SPP';
        }

        if (str_contains($lower, 'kantin')) {
            return 'Kantin';
        }

        if (str_contains($lower, 'top up') || str_contains($lower, 'titipan') || str_contains($lower, 'wallet')) {
            return 'Titipan Santri';
        }

        if (str_contains($lower, 'pemasukan kas') || str_contains($lower, 'pengeluaran kas')) {
            return 'Kas Sekolah';
        }

        if (str_contains($lower, 'beban') || str_contains($lower, 'listrik') || str_contains($lower, 'gaji') || str_contains($lower, 'operasional') || str_contains($lower, 'seragam')) {
            return 'Beban';
        }

        return 'Lainnya';
    }

    protected function accountBalances($accounts, ?int $branchId = null, array $period = [])
    {
        if ($accounts->isEmpty()) {
            return collect();
        }

        $ids = $accounts->pluck('id');

        $details = JournalDetail::whereIn('account_id', $ids)
            ->when($branchId, fn ($q) => $q->whereHas('journal', fn ($j) => $j->where('branch_id', $branchId)))
            ->when($period['from'] ?? null, fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '>=', $period['from'])))
            ->when($period['to'] ?? null, fn ($q) => $q->whereHas('journal', fn ($j) => $j->whereDate('transaction_date', '<=', $period['to'])))
            ->get(['account_id', 'debit', 'credit'])
            ->groupBy('account_id');

        return $accounts->map(function ($account) use ($details) {
            $accountDetails = $details->get($account->id, collect());

            $debit = $accountDetails->sum('debit');
            $credit = $accountDetails->sum('credit');
            $balance = in_array($account->type, ['asset', 'expense']) ? $debit - $credit : $credit - $debit;

            return (object) [
                'account' => $account,
                'balance' => $balance,
            ];
        });
    }

    protected function resolveBranchFilter(Request $request): ?int
    {
        if (! auth()->user()->isSuperadmin()) {
            return null;
        }

        $schoolId = $request->query('school_id');

        if (! $schoolId) {
            return null;
        }

        abort_unless(School::where('id', $schoolId)->exists(), 404);

        return (int) $schoolId;
    }

    protected function resolvePeriod(Request $request): array
    {
        $from = $request->query('from');
        $to = $request->query('to');

        return [
            'from' => $from && \Illuminate\Support\Carbon::hasFormat($from, 'Y-m-d') ? $from : null,
            'to' => $to && \Illuminate\Support\Carbon::hasFormat($to, 'Y-m-d') ? $to : null,
        ];
    }

    protected function withFilterData(array $data, Request $request, ?int $branchId): array
    {
        $data['schoolId'] = $request->query('school_id');
        $data['schools'] = auth()->user()->isSuperadmin()
            ? School::where('status', School::STATUS_ACTIVE)->orderBy('name')->get()
            : collect();

        $period = $this->resolvePeriod($request);
        $data['from'] = $period['from'];
        $data['to'] = $period['to'];

        return $data;
    }

    protected function streamPdf(Request $request, string $view, array $data, string $title, string $slug, ?int $branchId, array $period = []): Response
    {
        $schoolName = null;

        if ($branchId) {
            $schoolName = School::find($branchId)?->name;
        } elseif (auth()->user()->isSuperadmin()) {
            $schoolName = 'Semua Sekolah (Gabungan)';
        } else {
            $schoolName = auth()->user()->school?->name;
        }

        if ($period['from'] ?? null) {
            $title .= ' — Periode '.Carbon::parse($period['from'])->format('d/m/Y');
        }
        if ($period['to'] ?? null) {
            $title .= ' s.d. '.Carbon::parse($period['to'])->format('d/m/Y');
        }

        $data['title'] = $title;
        $data['subtitle'] = $schoolName
            ? $title.' — '.$schoolName
            : $title;

        $filename = sprintf('%s_%s.pdf', $slug, now()->format('Ymd_His'));

        return app(PdfService::class)->download($view, $data, $filename);
    }

    protected function streamExcel(string $filename, array $headers, array $rows, string $sheetTitle = 'Laporan')
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($sheetTitle);

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A1');

        $headerRange = 'A1:'.$lastColumn.'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF2F0EA');

        foreach ($rows as $index => $row) {
            $sheet->fromArray(array_values($row), null, 'A'.($index + 2));
        }

        foreach (range('A', $lastColumn) as $column) {
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
