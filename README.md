# SIPONPES — Sistem Informasi Pondok Pesantren

Aplikasi multi-sekolah: satu **superadmin** mengelola banyak sekolah (pondok pesantren/sekolah),
dan tiap sekolah memiliki **admin sendiri** untuk mengelola data santri, kelas, tabungan,
tagihan SPP, dan laporan keuangan.

## Stack

- **Laravel 12** (PHP 8.2)
- **Tailwind CSS v4** (Vite)
- **MySQL** (`pesantren_db`)
- **Alpine.js** (via CDN)

## Akun Uji

| Peran | Email | Password |
|---|---|---|
| Superadmin | `admin@siponpes.id` | `admin12345` |
| Admin demo | `admin.sekolah@siponpes.id` | `admin12345` |

## Perintah Penting

```bash
# Test
php artisan test

# Format kode (Pint)
vendor/bin/pint <file...>

# Build frontend
npm run build

# Kompilasi blade (setelah ubah view)
php artisan view:cache && php artisan view:clear

# Backup DB
mysqldump -u root pesantren_db > database/backup/pesantren_db_backup_$(date +%Y%m%d_%H%M%S).sql

# Buat akun admin sekolah
php artisan app:create-school-admin "email" "Nama" --password=admin12345 --school="Nama Sekolah"

# Backup database manual
php artisan backup:database
```

Akses lokal: **http://127.0.0.1:8000** (jangan pakai `localhost` — cookie domain beda).

## Fitur

- Auth multi-peran (superadmin / admin sekolah) + pendaftaran sekolah (menunggu persetujuan)
- Data sekolah (CRUD, approve/reject, reset password admin, API key per sekolah)
- Data kelas & santri (CRUD, filter per sekolah/kelas, import Excel)
- Tabungan santri (wallet): top-up, penarikan, batas pengeluaran harian, riwayat
- Invoice SPP: pembuatan otomatis bulanan, pembayaran via wallet, pengingat (email/WA placeholder)
- Tarif SPP per kelas / semua kelas
- Chart of Accounts (kode akun) — kelola akun untuk jurnal & laporan
- Laporan keuangan: jurnal umum, buku besar, neraca saldo, neraca, laba rugi + **export PDF**
- Backup database terjadwal (`php artisan backup:database`, tiap hari 02:00, simpan 15 backup terakhir)
- API kantin (autentikasi via `X-API-KEY` / bearer token per sekolah)

## Arsitektur & Konvensi

- **Isolasi tenant**: global scope `app/Models/Concerns/TenantScoped.php` membaca
  `auth()->user()->branch_id`; superadmin tidak kena scope (lihat semua sekolah).
- `JournalService::createJournalEntry()` selalu menerima `branch_id` eksplisit dari entitas
  (santri/sekolah) — jangan biarkan `branch_id` NULL.
- Breadcrumb konsisten: `Dashboard / Menu / Sekolah / Kelas`.
- Dokumentasi pengguna: `docs/manual-penggunaan-siponpes.html` / `.pdf`.
- Spesifikasi desain: `design-system/SIPONPES/MASTER.md`.
