<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    protected function forgetAccountCache(): void
    {
        Cache::forget('siponpes.accounts.revenue_ids');
        Cache::forget('siponpes.accounts.expense_ids');
    }

    public function index()
    {
        $accounts = Account::withCount('journalDetails')
            ->orderBy('account_code')
            ->get();

        return view('accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => ['required', 'string', 'max:20', 'unique:accounts,account_code'],
            'account_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
        ]);

        Account::create($validated);

        $this->forgetAccountCache();

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(Account $account)
    {
        return view('accounts.edit', compact('account'));
    }

    public function update(Request $request, Account $account)
    {
        $validated = $request->validate([
            'account_code' => ['required', 'string', 'max:20', Rule::unique('accounts')->ignore($account->id)],
            'account_name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
        ]);

        $account->update($validated);

        $this->forgetAccountCache();

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        if ($account->journalDetails()->exists()) {
            return redirect()->route('accounts.index')
                ->with('error', 'Akun sudah dipakai dalam jurnal dan tidak dapat dihapus.');
        }

        $account->delete();

        $this->forgetAccountCache();

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil dihapus.');
    }
}
