# SIPONPES — Design System Master

> Sistem Informasi Pondok Pesantren (multi-sekolah).
> Sumber acuan visual untuk seluruh halaman. Baca sebelum merombak UI apa pun.

## Konsep

**"Buku Register Pesantren"** — UI dirancang seperti buku catatan & buku besar pesantren:
kertas krem yang hangat, tabel bergaris seperti halaman jurnal, angka tabular, dan satu motif
belah ketupat ◆ sebagai pengingat seni geometris islami (dipakai hemat, tidak ramai).

## Warna (token Tailwind v4, `--color-*`)

| Token | Nilai | Pemakaian |
|---|---|---|
| `pine-700` | `#24503a` | Primer: tombol utama, sidebar, teks aksen |
| `pine-900` / `pine-950` | `#143025` / `#0b1f18` | Sidebar gradien, judul |
| `gold-500` / `gold-700` | `#b88a2e` / `#7d5818` | Aksen sparing: badge "menunggu", motif ◆ |
| `cream` | `#f6f1e4` | Latar aplikasi (kertas) |
| `paper` | `#fffdf7` | Kartu |
| `ink` | `#2b2923` | Teks hangat |

## Tipografi

- **Display**: `Fraunces` (serif berkarakter) — hanya untuk judul halaman & angka stat.
- **Body/UI**: `Instrument Sans` (sudah terpasang via fonts.bunny.net).
- Angka uang memakai `tabular-nums`.

## Motif khas (signature)

- **Garis ganda + berlian ◆** di bawah judul halaman: `div.page-title-rule` berisi svg ◆ di tengah dua garis.
- **Stat card**: angka serif besar + aksen kiri 4px warna tone + sub dengan ◆ kecil.
- **Kartu pilih-sekolah**: `.school-select` (paparan hover ringan, panah geser).

## Komponen & tata letak

- Sidebar hijau hutan (gradien `pine-900 → pine-950`), item aktif bergaris aksen emas kiri.
- Header kertas: judul serif + badge peran (+ nama sekolah untuk admin sekolah) + tombol Keluar.
- Tabel: `th` hijau muda (pine-50), baris bergaris `ink/10`, hover `pine-50/50`.
- Tombol: `btn btn-primary` (pine), `btn-secondary` (pine-100), `btn-danger` (rose), `btn-warning` (gold), `btn-ghost` (paper).
- Input/select: border `ink/20`, fokus ring pine.
- Aksesibilitas: fokus ring terlihat, `prefers-reduced-motion` dihormati, responsif 375–1440px.

## Aturan multi-sekolah (tenant)

- Superadmin: menu **Data Sekolah**, lapisan "pilih sekolah" di Kelas/Santri/Wallet, ringkasan semua sekolah.
- Admin sekolah: tanpa menu Data Sekolah; data terkunci ke sekolahnya (global scope `branch_id`);
  nama sekolah tampil di header.
- Semua query model tenant (Kelas, Santri, SppTariff, Journal, Invoice, Wallet, dst.) otomatis
  difilter via `App\Models\Concerns\TenantScoped` + `App\Support\Tenant`.

## Anti-pattern (hindari)

- Jangan menambah font/warna baru tanpa memperbarui file ini.
- Jangan memakai warna `slate`/`emerald` mentah di view — pakai token di atas.
- Jangan menghapus `.page-title-rule` dari judul halaman.
