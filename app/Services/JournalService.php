<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function createJournalEntry(string $description, string $reference, array $details, ?string $date = null, ?int $branchId = null): Journal
    {
        $date = $date ? $date : now()->toDateString();
        $branchId = $branchId ?? auth()->user()?->branch_id;

        return DB::transaction(function () use ($description, $reference, $details, $date, $branchId) {
            $journal = Journal::create([
                'transaction_date' => $date,
                'description' => $description,
                'reference' => $reference,
                'branch_id' => $branchId,
            ]);

            foreach ($details as $detail) {
                $account = Account::where('account_code', $detail['account_code'])->first();

                if (! $account) {
                    throw new \RuntimeException("Account with code {$detail['account_code']} not found.");
                }

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $account->id,
                    'debit' => $detail['debit'] ?? 0,
                    'credit' => $detail['credit'] ?? 0,
                ]);
            }

            return $journal;
        });
    }
}
