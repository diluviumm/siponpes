<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\School;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    use HandlesSchoolContext;

    public function index(Request $request)
    {
        $user = auth()->user();
        $schools = collect();
        $school = null;

        if ($user->isSuperadmin()) {
            $schoolId = $request->query('school_id');
            $school = $schoolId ? School::findOrFail($schoolId) : null;
            $schools = School::where('status', School::STATUS_ACTIVE)
                ->withCount('journals')
                ->orderBy('name')
                ->get();
        } else {
            $school = $user->school;
        }

        $accounts = Account::orderBy('account_code')->get();
        $closings = $school
            ? Journal::where('reference', 'like', 'CLOSING-%')->where('branch_id', $school->id)->orderByDesc('transaction_date')->get()
            : collect();

        return view('financial.adjusting', compact('accounts', 'closings', 'school', 'schools'));
    }

    public function store(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.account_id' => ['required', Rule::exists('accounts', 'id')],
            'entries.*.debit' => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $totalDebit = 0;
        $totalCredit = 0;
        $details = [];

        $accountsById = Account::whereIn('id', collect($validated['entries'])->pluck('account_id')->unique())
            ->get()
            ->keyBy('id');

        foreach ($validated['entries'] as $entry) {
            $debit = (float) ($entry['debit'] ?? 0);
            $credit = (float) ($entry['credit'] ?? 0);
            $totalDebit += $debit;
            $totalCredit += $credit;

            $account = $accountsById->get($entry['account_id']);
            $details[] = [
                'account_code' => $account->account_code,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return redirect()->back()->withInput()->withErrors(['entries' => 'Total debit harus sama dengan total kredit.']);
        }

        app(JournalService::class)->createJournalEntry(
            $validated['description'],
            'ADJ-'.now()->format('YmdHis'),
            $details,
            $validated['transaction_date'],
            $branchId
        );

        return redirect()->route('financial.adjusting', $this->schoolQuery($branchId))
            ->with('success', 'Jurnal penyesuaian berhasil dicatat.');
    }

    public function closeBook(Request $request)
    {
        $branchId = $this->resolveBranchId($request);

        $year = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ])['year'];

        if (Journal::where('reference', "CLOSING-{$year}")->where('branch_id', $branchId)->exists()) {
            return redirect()->route('financial.adjusting', $this->schoolQuery($branchId))
                ->with('error', "Buku tahun {$year} sudah pernah ditutup.");
        }

        $accounts = Account::whereIn('type', ['revenue', 'expense'])->get();
        $balances = $this->accountBalancesForYear($accounts, $branchId, $year);

        $details = [];

        foreach ($balances as $balance) {
            $amount = (float) $balance['balance'];

            if (abs($amount) <= 0.001) {
                continue;
            }

            $absAmount = abs($amount);
            $isPositive = $amount > 0;

            if ($balance['type'] === 'revenue') {
                if ($isPositive) {
                    $details[] = ['account_code' => $balance['code'], 'debit' => $absAmount, 'credit' => 0];
                    $details[] = ['account_code' => AccountCodes::RETAINED_EARNINGS, 'debit' => 0, 'credit' => $absAmount];
                } else {
                    $details[] = ['account_code' => AccountCodes::RETAINED_EARNINGS, 'debit' => $absAmount, 'credit' => 0];
                    $details[] = ['account_code' => $balance['code'], 'debit' => 0, 'credit' => $absAmount];
                }
            } elseif ($isPositive) {
                $details[] = ['account_code' => AccountCodes::RETAINED_EARNINGS, 'debit' => $absAmount, 'credit' => 0];
                $details[] = ['account_code' => $balance['code'], 'debit' => 0, 'credit' => $absAmount];
            } else {
                $details[] = ['account_code' => $balance['code'], 'debit' => $absAmount, 'credit' => 0];
                $details[] = ['account_code' => AccountCodes::RETAINED_EARNINGS, 'debit' => 0, 'credit' => $absAmount];
            }
        }

        if (empty($details)) {
            return redirect()->route('financial.adjusting', $this->schoolQuery($branchId))
                ->with('error', "Tidak ada pendapatan/beban untuk ditutup pada tahun {$year}.");
        }

        app(JournalService::class)->createJournalEntry(
            "Penutupan buku tahun {$year}",
            "CLOSING-{$year}",
            $details,
            "{$year}-12-31",
            $branchId
        );

        return redirect()->route('financial.adjusting', $this->schoolQuery($branchId))
            ->with('success', "Buku tahun {$year} berhasil ditutup. Surplus/defisit dipindah ke akun Laba Ditahan.");
    }

    protected function accountBalancesForYear($accounts, int $branchId, int $year): array
    {
        $ids = $accounts->pluck('id');

        $rows = JournalDetail::whereIn('account_id', $ids)
            ->whereHas('journal', fn ($q) => $q->where('branch_id', $branchId)->whereYear('transaction_date', $year))
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $result = [];

        foreach ($accounts as $account) {
            $row = $rows->get($account->id);
            $debit = (float) ($row->total_debit ?? 0);
            $credit = (float) ($row->total_credit ?? 0);
            $balance = $account->type === 'revenue' ? $credit - $debit : $debit - $credit;

            if (abs($balance) > 0.001) {
                $result[] = [
                    'code' => $account->account_code,
                    'type' => $account->type,
                    'balance' => $balance,
                ];
            }
        }

        return $result;
    }
}
