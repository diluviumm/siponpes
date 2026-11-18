<div style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>Atur Ulang Password</h2>
    <p>Halo, kami menerima permintaan untuk mengatur ulang password akun SIPONPES Anda.</p>
    <p>
        <a href="{{ $resetUrl }}" style="display:inline-block; background:#1f3b2c; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none;">
            Atur Ulang Password
        </a>
    </p>
    <p>Tautan berlaku selama {{ $expiresInMinutes }} menit. Jika Anda tidak meminta ini, abaikan email ini.</p>
    <p>Jika tombol di atas tidak berfungsi, salin tautan berikut ke browser:</p>
    <p style="word-break: break-all; color:#555;">{{ $resetUrl }}</p>
    <p>Terima kasih.</p>
</div>
