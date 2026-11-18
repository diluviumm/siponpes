<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $resetUrl;

    public string $expiresInMinutes;

    public function __construct(string $resetUrl, int $expiresInMinutes = 60)
    {
        $this->resetUrl = $resetUrl;
        $this->expiresInMinutes = (string) $expiresInMinutes;
    }

    public function build()
    {
        return $this->subject('Atur Ulang Password — SIPONPES')
            ->view('emails.password_reset');
    }
}
