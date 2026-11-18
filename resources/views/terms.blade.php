@extends('layouts.static')

@section('title', 'Syarat Layanan — SIPONPES')

@section('content')
<div class="card card-body">
    <div class="text-center mb-8">
        <h1 class="font-display text-3xl font-semibold text-pine-900">Syarat Layanan</h1>
        <x-page-title-rule width="max-w-xs mx-auto mt-4" />
        <p class="text-sm text-ink/60 mt-4">Terakhir diperbarui: 24 Agustus 2026</p>
    </div>

    <div class="space-y-6 text-sm leading-relaxed text-ink/80">
        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">1. Penerimaan Ketentuan</h2>
            <p>Dengan menggunakan SIPONPES, Anda dianggap telah membaca dan menyetujui syarat layanan ini. Jika Anda tidak setuju, jangan gunakan aplikasi ini.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">2. Layanan</h2>
            <p>SIPONPES adalah sistem informasi pengelolaan pondok pesantren yang menyediakan modul data santri, kelas, tabungan, tagihan SPP, kas sekolah, kasir kantin, dan laporan keuangan. Layanan bersifat multi-sekolah: satu superadmin mengelola banyak sekolah, dan setiap sekolah memiliki admin sendiri.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">3. Akun &amp; Tanggung Jawab Pengguna</h2>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Anda bertanggung jawab menjaga kerahasiaan email dan kata sandi akun Anda.</li>
                <li>Setiap aktivitas yang terjadi pada akun Anda adalah tanggung jawab Anda.</li>
                <li>Laporkan segera kepada pengelola bila ada akses tidak sah ke akun Anda.</li>
                <li>Akun baru berstatus <em>menunggu persetujuan</em> dan wajib verifikasi email sebelum dapat digunakan penuh.</li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">4. Penggunaan yang Wajar</h2>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Gunakan aplikasi hanya untuk keperluan pengelolaan sekolah yang sah.</li>
                <li>Dilarang menyalahgunakan akses, mencoba mengakses data sekolah lain, atau mengganggu sistem.</li>
                <li>Pengelola berhak menonaktifkan akun yang melanggar ketentuan.</li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">5. Data &amp; Akurasi</h2>
            <p>Data yang dimasukkan menjadi tanggung jawab sekolah masing-masing. Transaksi keuangan dicatat secara otomatis ke jurnal; pastikan data yang Anda input benar karena memengaruhi laporan keuangan.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">6. Ketersediaan Layanan</h2>
            <p>Kami berusaha menjaga ketersediaan layanan, tetapi tidak menjamin bebas gangguan. Pemeliharaan sistem dapat dilakukan sewaktu-waktu. Pastikan selalu memanfaatkan cadangan (backup) database untuk keamanan data Anda.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">7. Perubahan Ketentuan</h2>
            <p>Ketentuan ini dapat diperbarui sewaktu-waktu. Perubahan akan diumumkan melalui aplikasi. Penggunaan lanjutan setelah perubahan dianggap menyetujui ketentuan terbaru.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">8. Kontak</h2>
            <p>Untuk pertanyaan terkait syarat layanan, hubungi pengelola aplikasi SIPONPES.</p>
        </div>
    </div>
</div>
@endsection
