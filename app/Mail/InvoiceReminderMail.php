<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public string $messageText;

    public function __construct(Invoice $invoice, string $messageText)
    {
        $this->invoice = $invoice;
        $this->messageText = $messageText;
    }

    public function build()
    {
        return $this->subject("Pengingat Invoice {$this->invoice->invoice_number}")
            ->view('emails.invoice_reminder')
            ->with([
                'invoice' => $this->invoice,
                'messageText' => $this->messageText,
            ]);
    }
}
