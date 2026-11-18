<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesSchoolContext;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Models\WalletTransaction;
use App\Services\JournalService;
use App\Support\AccountCodes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
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

        $kelasId = $request->query('kelas_id');
        $search = $request->query('search');

        $kelasList = collect();
        $totalSaldo = 0;
        $totalLimit = 0;

        if ($school) {
            $kelasList = Kelas::where('branch_id', $school->id)
                ->with('santris.wallet')
                ->withCount('santris')
                ->get()
                ->map(function ($kelas) {
                    $kelas->total_saldo = $kelas->santris->sum(fn ($s) => $s->wallet?->balance ?? 0);
                    $kelas->total_limit = $kelas->santris->sum(fn ($s) => $s->wallet?->daily_spending_limit ?? 0);

                    return $kelas;
                });

            $totalSaldo = $kelasList->sum('total_saldo');
            $totalLimit = $kelasList->sum('total_limit');
        }

        $santris = collect();
        $selectedKelas = null;
        $isGlobalSearch = false;

        if ($school && $search && ! $kelasId) {
            $isGlobalSearch = true;
            $santris = Santri::with(['kelas', 'school', 'wallet'])
                ->where('branch_id', $school->id)
                ->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                })
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        } elseif ($school && $kelasId) {
            $selectedKelas = $kelasList->firstWhere('id', (int) $kelasId);
            $santris = Santri::with(['kelas', 'school', 'wallet'])
                ->where('kelas_id', $kelasId)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sq) use ($search) {
                        $sq->where('nama', 'like', "%{$search}%")
                            ->orWhere('nis', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString();
        }

        return view('wallet.index', compact(
            'kelasList',
            'santris',
            'selectedKelas',
            'kelasId',
            'search',
            'isGlobalSearch',
            'school',
            'schools',
            'totalSaldo',
            'totalLimit'
        ));
    }

    public function editLimit(Santri $santri)
    {
        $santri->load('wallet');

        return view('wallet.edit-limit', compact('santri'));
    }

    public function updateLimit(Request $request, Santri $santri)
    {
        $request->validate([
            'daily_spending_limit' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($santri, $request) {
            $wallet = $santri->wallet()->firstOrCreate([
                'santri_id' => $santri->id,
            ], [
                'balance' => 0,
                'daily_spending_limit' => 0,
            ]);

            $wallet->update([
                'daily_spending_limit' => $request->input('daily_spending_limit'),
            ]);
        });

        return redirect()->route('wallet.index', $this->schoolQuery($santri->branch_id))
            ->with('success', 'Batas pengeluaran harian berhasil diperbarui.');
    }

    public function editTransaction(Santri $santri)
    {
        $santri->load('wallet');

        return view('wallet.edit-transaction', compact('santri'));
    }

    public function history(Santri $santri)
    {
        $santri->load('wallet');

        $transactions = $santri->wallet
            ? $santri->wallet->transactions()->orderByDesc('created_at')->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('wallet.history', compact('santri', 'transactions'));
    }

    public function processTransaction(Request $request, Santri $santri)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:debit,credit',
            'description' => 'nullable|string|max:255',
            'reference_id' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($santri, $request) {
            $wallet = $santri->wallet()->lockForUpdate()->first();

            if (! $wallet) {
                try {
                    $wallet = $santri->wallet()->firstOrCreate([
                        'santri_id' => $santri->id,
                    ], [
                        'balance' => 0,
                        'daily_spending_limit' => 0,
                    ]);
                } catch (UniqueConstraintViolationException $e) {
                    $wallet = $santri->wallet()->lockForUpdate()->firstOrFail();
                }
            }

            $amount = $request->input('amount');
            $type = $request->input('type');
            $newBalance = $wallet->balance;

            if ($type === 'credit') {
                $newBalance += $amount;
            } else {
                if ($wallet->balance < $amount) {
                    throw new HttpResponseException(
                        redirect()->back()
                            ->withInput()
                            ->withErrors(['amount' => 'Saldo tidak cukup. Saldo saat ini: Rp '.number_format($wallet->balance, 2, ',', '.')])
                    );
                }

                $todayDebit = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('type', 'debit')
                    ->whereDate('created_at', Carbon::today())
                    ->sum('amount');

                if ($wallet->daily_spending_limit > 0 && ($todayDebit + $amount) > $wallet->daily_spending_limit) {
                    $remainingLimit = $wallet->daily_spending_limit - $todayDebit;
                    throw new HttpResponseException(
                        redirect()->back()
                            ->withInput()
                            ->withErrors(['amount' => 'Melebihi batas harian. Sisa limit: Rp '.number_format($remainingLimit, 2, ',', '.')])
                    );
                }

                $newBalance -= $amount;
            }

            $wallet->update(['balance' => $newBalance]);

            $walletTransaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $type,
                'description' => $request->input('description'),
                'reference_id' => $request->input('reference_id'),
            ]);

            if ($type === 'credit') {
                app(JournalService::class)->createJournalEntry(
                    "Top up wallet untuk santri {$santri->nama}",
                    "WALLET-{$wallet->id}",
                    [
                        ['account_code' => AccountCodes::CASH, 'debit' => $amount, 'credit' => 0],
                        ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => 0, 'credit' => $amount],
                    ],
                    now()->toDateString(),
                    $santri->branch_id
                );
            } else {
                app(JournalService::class)->createJournalEntry(
                    "Penarikan wallet untuk santri {$santri->nama}",
                    "WALLET-DEBIT-{$walletTransaction->id}",
                    [
                        ['account_code' => AccountCodes::WALLET_LIABILITY, 'debit' => $amount, 'credit' => 0],
                        ['account_code' => AccountCodes::CASH, 'debit' => 0, 'credit' => $amount],
                    ],
                    now()->toDateString(),
                    $santri->branch_id
                );
            }
        });

        return redirect()->route('wallet.index', $this->schoolQuery($santri->branch_id))
            ->with('success', 'Transaksi wallet berhasil diproses.');
    }
}
