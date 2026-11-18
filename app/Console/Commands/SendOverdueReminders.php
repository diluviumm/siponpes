<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceReminderService;
use Illuminate\Console\Command;

class SendOverdueReminders extends Command
{
    protected $signature = 'billing:send-overdue-reminders';

    protected $description = 'Send reminders for overdue unpaid invoices.';

    public function handle()
    {
        $invoices = Invoice::with(['santri'])
            ->where('status', Invoice::STATUS_UNPAID)
            ->whereDate('due_date', '<', now())
            ->get();

        $sentCount = 0;
        $skippedCount = 0;
        $noContactCount = 0;

        $service = app(InvoiceReminderService::class);

        foreach ($invoices as $invoice) {
            $message = $service->buildMessage($invoice, true);
            $channel = $service->sendReminder($invoice, $message);

            if ($channel === 'skipped') {
                $skippedCount++;
            } elseif ($channel === 'none') {
                $noContactCount++;
            } else {
                $sentCount++;
            }
        }

        $this->info("Sent reminders for {$sentCount} overdue invoice(s).");

        if ($skippedCount > 0) {
            $this->info("Skipped {$skippedCount} invoice(s) already reminded recently.");
        }

        if ($noContactCount > 0) {
            $this->info("Skipped {$noContactCount} invoice(s) with no email or guardian phone contact.");
        }

        return 0;
    }
}
