<?php

namespace App\Services;

use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceReminderService
{
    public function buildMessage(Invoice $invoice, bool $overdue = false): string
    {
        $nominal = number_format($invoice->total_amount, 2, ',', '.');
        $nama = $invoice->santri->nama;
        $nomor = $invoice->invoice_number;
        $jatuhTempo = $invoice->due_date->format('d/m/Y');

        if ($overdue) {
            return sprintf(
                'Halo %s, tagihan SPP nomor %s sebesar Rp %s sudah jatuh tempo pada %s. Mohon segera lakukan pembayaran.',
                $nama,
                $nomor,
                $nominal,
                $jatuhTempo
            );
        }

        return sprintf(
            'Halo %s, tagihan Anda dengan nomor %s sebesar Rp %s jatuh tempo pada %s. Mohon lakukan pembayaran segera.',
            $nama,
            $nomor,
            $nominal,
            $jatuhTempo
        );
    }

    public function sendReminder(Invoice $invoice, string $message, bool $force = false): string
    {
        if (! $force && $this->wasRecentlyReminded($invoice)) {
            return 'skipped';
        }

        $invoice->loadMissing('santri');

        $channel = 'none';

        if ($invoice->santri->email) {
            try {
                Mail::to($invoice->santri->email)->queue(new InvoiceReminderMail($invoice, $message));
                $channel = 'email';
            } catch (\Throwable $exception) {
                Log::error('Invoice reminder email gagal dikirim (SMTP).', [
                    'invoice_id' => $invoice->id,
                    'to' => $invoice->santri->email,
                    'error' => $exception->getMessage(),
                ]);

                $wa = $this->sendWhatsApp($invoice, $message);

                $channel = $wa === 'wa' ? $wa : 'email_failed';
            }
        } else {
            $channel = $this->sendWhatsApp($invoice, $message);
        }

        InvoiceReminder::create([
            'invoice_id' => $invoice->id,
            'channel' => $channel,
            'message' => $message,
            'sent_at' => now(),
        ]);

        return $channel;
    }

    protected function wasRecentlyReminded(Invoice $invoice, int $hours = 24): bool
    {
        return InvoiceReminder::where('invoice_id', $invoice->id)
            ->where('sent_at', '>=', now()->subHours($hours))
            ->exists();
    }

    /**
     * Kirim pengingat via WhatsApp menggunakan Fonnte.
     * Tanpa token Fonnte, dicatat sebagai placeholder (log) agar tidak gagal.
     */
    protected function sendWhatsApp(Invoice $invoice, string $message): string
    {
        $phone = $invoice->santri->no_hp_wali;

        if (! $phone) {
            Log::warning('Invoice reminder could not be delivered: no email and no guardian phone number.', [
                'invoice_id' => $invoice->id,
                'santri_id' => $invoice->santri->id,
            ]);

            return 'none';
        }

        $token = config('whatsapp.fonnte.token');

        if (! $token) {
            $this->logWaPlaceholder($invoice, $message);

            return 'none';
        }

        try {
            $response = Http::withHeaders(['Authorization' => $token])
                ->timeout(15)
                ->post(config('whatsapp.fonnte.url'), [
                    'target' => $phone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return 'wa';
            }

            Log::warning('Fonnte WhatsApp send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'none';
        } catch (\Throwable $exception) {
            Log::warning('Fonnte WhatsApp exception: '.$exception->getMessage());

            return 'none';
        }
    }

    protected function logWaPlaceholder(Invoice $invoice, string $message): void
    {
        if ($invoice->santri->no_hp_wali) {
            Log::info("[WA Placeholder] Send reminder to {$invoice->santri->no_hp_wali}: {$message}");
        }
    }
}
