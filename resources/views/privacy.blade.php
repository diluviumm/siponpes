@extends('layouts.static')

@section('title', 'Kebijakan Privasi — SIPONPES')

@section('content')
<div class="card card-body">
    <div class="text-center mb-8">
        <h1 class="font-display text-3xl font-semibold text-pine-900">Kebijakan Privasi</h1>
        <x-page-title-rule width="max-w-xs mx-auto mt-4" />
        <p class="text-sm text-ink/60 mt-4">Terakhir diperbarui: 24 Agustus 2026</p>
    </div>

    <div class="space-y-6 text-sm leading-relaxed text-ink/80">
        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">1. Informasi yang Kami Kumpulkan</h2>
            <p>SIPONPES mengumpulkan data yang dibutuhkan untuk pengelolaan pondok pesantren, yaitu:</p>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li><strong>Data sekolah</strong> — nama, alamat, dan nomor telepon.</li>
                <li><strong>Data akun pengguna</strong> — nama, email, dan kata sandi (tersimpan terenkripsi).</li>
                <li><strong>Data santri</strong> — NIS, nama, kelas, jenis kelamin, data kelahiran, alamat, dan token kartu.</li>
                <li><strong>Data wali</strong> — nama, nomor telepon, dan email (untuk pengiriman pengingat tagihan).</li>
                <li><strong>Data keuangan</strong> — saldo tabungan, transaksi, tagihan SPP, kas, dan jurnal.</li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">2. Penggunaan Data</h2>
            <p>Data digunakan untuk keperluan operasional aplikasi, antara lain:</p>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Mengelola data santri, kelas, dan tagihan SPP.</li>
                <li>Mencatat tabungan dan transaksi keuangan secara akurat.</li>
                <li>Mengirim pengingat tagihan dan tautan verifikasi/reset melalui email atau WhatsApp.</li>
                <li>Menyusun laporan keuangan untuk keperluan sekolah/yayasan.</li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">3. Keamanan Data</h2>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Data setiap sekolah <strong>terisolasi</strong> — admin/kasir hanya dapat melihat data sekolahnya sendiri.</li>
                <li>Kata sandi disimpan dalam bentuk <em>hash</em>, tidak pernah disimpan sebagai teks biasa.</li>
                <li>Setiap perubahan data tercatat dalam <strong>Audit Trail</strong>.</li>
                <li>Koneksi dapat dilindungi HTTPS dan header keamanan aktif.</li>
                <li>Database dicadangkan (<em>backup</em>) secara berkala.</li>
            </ul>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">4. Berbagi Data</h2>
            <p>Kami tidak menjual atau menyewakan data. Data hanya diakses oleh pihak yang memiliki peran dan sekolah yang berhak. Data wali hanya digunakan untuk pengiriman pengingat tagihan terkait akun tersebut.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">5. Penyimpanan &amp; Retensi</h2>
            <p>Data disimpan selama sekolah tetap terdaftar dan dibutuhkan. Penghapusan permanen sekolah akan menghapus data terkait dari sistem; penghapusan santri bersifat <em>soft delete</em> agar riwayat keuangan tetap dapat dipertanggungjawabkan.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">6. Hak Anda</h2>
            <p>Anda dapat mengakses, memperbaiki, atau meminta penghapusan data sekolah Anda dengan menghubungi superadmin/pengelola aplikasi. Anda juga dapat mengubah profil dan password melalui menu <strong>Profil Saya</strong>.</p>
        </div>

        <div>
            <h2 class="font-display text-lg font-semibold text-pine-900 mb-2">7. Kontak</h2>
            <p>Jika ada pertanyaan terkait kebijakan privasi ini, silakan hubungi pengelola aplikasi SIPONPES.</p>
        </div>
    </div>
</div>
@endsection
