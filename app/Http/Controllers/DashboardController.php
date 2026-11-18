<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Models\JournalDetail;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperadmin = $user->isSuperadmin();

        $branchId = null;
        $selectedSchool = null;
        $pendingSchools = collect();
        $totalSchoolCount = 0;
        $totalActiveSchoolCount = 0;
        $schools = collect();

        if ($isSuperadmin) {
            $pendingSchools = School::where('status', School::STATUS_PENDING)->withCount('santris')->get();
            $totalSchoolCount = School::count();
            $totalActiveSchoolCount = School::where('status', School::STATUS_ACTIVE)->count();

            $walletStats = Wallet::join('santris', 'santris.id', '=', 'wallets.santri_id')
                ->whereNull('santris.deleted_at')
                ->selectRaw('santris.branch_id, SUM(wallets.balance) as total_saldo, SUM(wallets.daily_spending_limit) as total_limit')
                ->groupBy('santris.branch_id')
                ->get()
                ->keyBy('branch_id');

            $invoiceStats = Invoice::join('santris', 'santris.id', '=', 'invoices.santri_id')
                ->whereNull('santris.deleted_at')
                ->selectRaw('santris.branch_id, COUNT(*) as total_invoices, SUM(CASE WHEN invoices.status = ? THEN 1 ELSE 0 END) as unpaid_invoices', [Invoice::STATUS_UNPAID])
                ->groupBy('santris.branch_id')
                ->get()
                ->keyBy('branch_id');

            $schools = School::where('status', School::STATUS_ACTIVE)
                ->withCount(['santris', 'kelas'])
                ->orderBy('name')
                ->get()
                ->map(function ($school) use ($walletStats, $invoiceStats) {
                    $wallet = $walletStats->get($school->id);
                    $invoice = $invoiceStats->get($school->id);

                    $school->total_saldo = $wallet->total_saldo ?? 0;
                    $school->total_limit = $wallet->total_limit ?? 0;
                    $school->total_invoices = $invoice->total_invoices ?? 0;
                    $school->unpaid_invoices = $invoice->unpaid_invoices ?? 0;

                    return $school;
                });

            $schoolId = $request->query('school_id');

            if ($schoolId) {
                $selectedSchool = School::findOrFail($schoolId);
                $branchId = $selectedSchool->id;
            }
        } else {
            $selectedSchool = $user->school;
            $branchId = $selectedSchool ? $selectedSchool->id : null;
        }

        $kelasQuery = Kelas::query();
        $santriQuery = Santri::query();
        $walletQuery = Wallet::query();
        $invoiceQuery = Invoice::query();
        $reminderQuery = InvoiceReminder::query();

        if ($branchId) {
            $kelasQuery->where('branch_id', $branchId);
            $santriQuery->where('branch_id', $branchId);
            $walletQuery->whereHas('santri', fn ($q) => $q->where('branch_id', $branchId));
            $invoiceQuery->filterSchool($branchId);
            $reminderQuery->whereHas('invoice.santri', fn ($q) => $q->where('branch_id', $branchId));
        }

        $totalKelas = (clone $kelasQuery)->count();
        $totalSantri = (clone $santriQuery)->count();
        $totalWalletBalance = (clone $walletQuery)->sum('balance');
        $totalDailyLimit = (clone $walletQuery)->sum('daily_spending_limit');
        $totalInvoices = (clone $invoiceQuery)->count();
        $totalUnpaidInvoices = (clone $invoiceQuery)->where('status', Invoice::STATUS_UNPAID)->count();
        $totalOverdueInvoices = (clone $invoiceQuery)
            ->where('status', Invoice::STATUS_UNPAID)
            ->whereDate('due_date', '<', now())
            ->count();
        $totalUpcomingDueInvoices = (clone $invoiceQuery)
            ->where('status', Invoice::STATUS_UNPAID)
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->count();
        $totalOutstandingAmount = (clone $invoiceQuery)->where('status', Invoice::STATUS_UNPAID)->sum('total_amount');
        $totalRemindersSentLast7Days = (clone $reminderQuery)->where('sent_at', '>=', now()->subDays(7))->count();

        [$chartLabels, $chartRevenue, $chartExpense] = $this->monthlyTrend($branchId);

        $school = $selectedSchool;

        return view('dashboard.index', compact(
            'isSuperadmin',
            'school',
            'schools',
            'branchId',
            'pendingSchools',
            'totalSchoolCount',
            'totalActiveSchoolCount',
            'totalKelas',
            'totalSantri',
            'totalWalletBalance',
            'totalDailyLimit',
            'totalInvoices',
            'totalUnpaidInvoices',
            'totalOverdueInvoices',
            'totalUpcomingDueInvoices',
            'totalOutstandingAmount',
            'totalRemindersSentLast7Days',
            'chartLabels',
            'chartRevenue',
            'chartExpense'
        ));
    }

    protected function monthlyTrend(?int $branchId): array
    {
        $revenueIds = Cache::rememberForever('siponpes.accounts.revenue_ids', fn () => Account::where('type', 'revenue')->pluck('id'));
        $expenseIds = Cache::rememberForever('siponpes.accounts.expense_ids', fn () => Account::where('type', 'expense')->pluck('id'));

        $revenueList = $revenueIds->isNotEmpty() ? implode(',', $revenueIds->all()) : '0';
        $expenseList = $expenseIds->isNotEmpty() ? implode(',', $expenseIds->all()) : '0';

        $ymExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', journals.transaction_date)"
            : "DATE_FORMAT(journals.transaction_date, '%Y-%m')";

        $monthly = JournalDetail::join('journals', 'journals.id', '=', 'journal_details.journal_id')
            ->whereBetween('journals.transaction_date', [
                now()->startOfMonth()->subMonths(11)->toDateString(),
                now()->addMonth()->startOfMonth()->toDateString(),
            ])
            ->when($branchId, fn ($q) => $q->where('journals.branch_id', $branchId))
            ->selectRaw("{$ymExpression} as ym")
            ->selectRaw("SUM(CASE WHEN journal_details.account_id IN ({$revenueList}) THEN journal_details.credit ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN journal_details.account_id IN ({$expenseList}) THEN journal_details.debit ELSE 0 END) as expense")
            ->groupBy('ym')
            ->get()
            ->keyBy('ym');

        $labels = [];
        $revenue = [];
        $expense = [];

        foreach (range(11, 0) as $i) {
            $month = now()->startOfMonth()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->locale('id')->translatedFormat('M y');
            $revenue[] = round((float) ($monthly->get($key)?->revenue ?? 0), 2);
            $expense[] = round((float) ($monthly->get($key)?->expense ?? 0), 2);
        }

        return [$labels, $revenue, $expense];
    }
}
