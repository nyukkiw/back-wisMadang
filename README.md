# Wis Madang — Backend (API)

Backend REST API untuk aplikasi Wis Madang (cafe & catering), dibangun dengan Laravel 12 + Sanctum. Menangani autentikasi, katalog menu & paket catering, keranjang, checkout dengan pembayaran Midtrans, ulasan pelanggan dengan analisis sentimen AI, serta dashboard ringkasan bisnis.

Frontend-nya ada di repo terpisah: **[front-wisMadang](https://github.com/nyukkiw/front-wisMadang)**. Backend ini harus dijalankan lebih dulu sebelum frontend, karena frontend mengambil semua data dari sini.

## Yang dibutuhkan sebelum mulai

- PHP 8.2 atau lebih baru
- Composer
- MySQL (atau MariaDB)
- Akun [Midtrans Sandbox](https://dashboard.sandbox.midtrans.com/) (gratis) — untuk fitur pembayaran
- (Opsional) API key [Tencent EdgeOne AI Gateway](https://edgeone.ai/) — untuk fitur analisis sentimen & insight AI pada ulasan. Tanpa ini, aplikasi tetap jalan normal, cuma fitur AI-nya saja yang tidak aktif (diam-diam dilewati, bukan error).

## 1. Install dependency

```bash
composer install
```

## 2. Siapkan file environment

```bash
cp .env.example .env
php artisan key:generate
```

Buka file `.env`, lalu atur bagian database ke MySQL (ganti `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sesuai MySQL di komputer kamu):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wis_madang
DB_USERNAME=root
DB_PASSWORD=
```

Isi juga kunci Midtrans Sandbox (dari dashboard.sandbox.midtrans.com > Settings > Access Keys):

```env
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
```

Dan kalau mau mengaktifkan fitur AI pada ulasan, isi juga:

```env
EDGEONE_AI_API_KEY=
```

## 3. Buat database & import struktur tabel

Sebagian besar tabel di aplikasi ini (`pengguna`, `menu`, `pesanan`, `keranjang`, `ulasan`, dll) dibuat langsung sebagai desain database, bukan lewat migration satu-satu. Jadi, seluruh struktur tabel (termasuk tabel bawaan Laravel) diimport sekaligus dari file SQL.

Buat database kosong bernama `wis_madang` (atau nama lain, asal sama dengan `DB_DATABASE` di `.env`), lalu import strukturnya:

```bash
mysql -u root -p wis_madang < database/sql/wis_madang.sql
```

> File ini **cuma berisi struktur tabel, tidak ada data**. Semua tabel masih kosong setelah ini.

Jalankan `migrate` buat memastikan semua migration tercatat sinkron (harusnya langsung muncul "Nothing to migrate", itu tandanya berhasil):

```bash
php artisan migrate
```

## 4. Isi data contoh (seeder)

```bash
php artisan db:seed
```

Perintah ini akan membuat:
- **1 akun penjual (admin) contoh**, buat login dan coba fitur Dashboard/Kasir/Kelola Menu/Analisis Ulasan — lihat kredensialnya di bagian "Akun contoh" di bawah
- 4 kategori menu + 7 menu contoh
- 3 paket catering contoh

Akun pelanggan tidak perlu di-seed — bisa daftar sendiri lewat halaman Register di frontend.

## 5. Buat symlink storage (untuk gambar yang diupload)

```bash
php artisan storage:link
```

Tanpa ini, gambar menu/paket catering yang diupload lewat halaman admin tidak akan muncul di frontend.

## 6. Jalankan server

```bash
php artisan serve
```

Backend akan jalan di `http://127.0.0.1:8000`. **Pastikan tetap di host & port ini** — frontend dan konfigurasi CORS (`config/cors.php`) sudah diatur khusus untuk alamat ini.

## Akun contoh

| Peran | Email | Password |
|---|---|---|
| Penjual (admin) | `penjual@wismadang.com` | `penjual123` |

Untuk akun pelanggan, daftar akun baru sendiri lewat halaman Register di frontend.

## Catatan untuk pengujian pembayaran (Midtrans Sandbox)

Karena `MIDTRANS_IS_PRODUCTION=false`, semua pembayaran yang dicoba adalah simulasi (tidak ada uang asli yang berpindah). Untuk mensimulasikan pembayaran kartu kredit berhasil di popup Midtrans, gunakan [kartu uji Midtrans](https://docs.midtrans.com/docs/testing-payment-on-sandbox):

- Nomor kartu: `4811 1111 1111 1114`
- CVV: `123`
- Masa berlaku: tanggal apa saja di masa depan
- OTP/3DS (jika diminta): `112233`

## Catatan teknis lain

- Autentikasi pakai token (Laravel Sanctum), bukan cookie session — frontend menyimpan token dan mengirimkannya lewat header `Authorization: Bearer <token>`.
- Role pengguna cuma ada 2: `pelanggan` dan `penjual` (tidak ada role terpisah untuk kasir — kasir memakai akun `penjual` yang sama).
- Endpoint publik (`GET /api/menu`, `GET /api/paket-catering`, dll) bisa diakses tanpa login. Endpoint di bawah `/api/v1/*` butuh token, dan sebagian (menu/paket CRUD, dashboard, insight AI) khusus untuk role `penjual`.
