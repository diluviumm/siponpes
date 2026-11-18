<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireOverdueInvoices extends Command
{
    protected $signature = 'billing:expire-overdue';

    protected $description = 'Mark unpaid invoices as expired when due date has passed.';

    public function handle()
    {
        $this->info('Checking for overdue invoices...');

        $expiredCount = DB::transaction(function () {
            $items = Invoice::where('status', Invoice::STATUS_UNPAID)
                ->whereDate('due_date', '<', Carbon::today())
                ->get();

            foreach ($items as $invoice) {
                $invoice->update(['status' => Invoice::STATUS_EXPIRED]);
            }

            return $items->count();
        });

        $this->info("Expired {$expiredCount} overdue invoice(s).");

        return 0;
    }
}
