# KasMe — Personal Finance Manager

KasMe adalah aplikasi pengelolaan keuangan pribadi berbasis Laravel dan Blade yang dirancang untuk shared hosting konvensional. Aplikasi mencakup akun, transaksi dan lampiran privat, transfer, anggaran, tagihan, utang/piutang, target tabungan, laporan, ekspor, dan preferensi pengguna.

## Persyaratan

- PHP 8.3 atau lebih baru beserta ekstensi Laravel yang dibutuhkan
- Composer
- MySQL atau MariaDB
- Node.js 20+ dan npm hanya pada lingkungan build

## Instalasi lokal

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
```

Buat basis data kosong, isi konfigurasi `DB_*` pada `.env`, kemudian jalankan:

```bash
php artisan migrate
npm run build
```

Untuk pengembangan lokal, jalankan `php artisan serve` dan `npm run dev` pada terminal terpisah. Jangan gunakan kedua proses tersebut sebagai runtime produksi.

## Persiapan produksi

Gunakan konfigurasi minimum berikut pada `.env` produksi:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.example
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

Document root domain harus diarahkan ke direktori `public/`. Jangan menaruh `.env`, direktori `storage/app/private`, atau source aplikasi di document root publik.

Bangun paket rilis dan cache Laravel dengan:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Unggah hasil `public/build`, tetapi jangan unggah `node_modules`. Produksi tidak membutuhkan Node.js, Docker, Redis, Supervisor, PM2, WebSocket server, maupun queue worker persisten.

Direktori `storage/` dan `bootstrap/cache/` harus dapat ditulis PHP dengan izin minimum yang didukung hosting. Lampiran transaksi berada di `storage/app/private/transactions/{user_id}` dan disajikan melalui controller berotorisasi; lampiran tersebut tidak memerlukan `storage:link`.

## Verifikasi setelah deployment

```bash
php artisan about
php artisan migrate:status
php artisan route:list
```

Uji login/logout, dashboard, pembuatan transaksi, rekonsiliasi transfer, unduhan lampiran, laporan, ekspor, dan penolakan akses lintas pengguna. Pastikan log pada `storage/logs` tidak memuat error kritis.

Jika konfigurasi atau route berubah setelah deployment, jalankan `php artisan optimize:clear`, lalu buat ulang cache produksi. Selalu cadangkan basis data dan direktori lampiran privat sebelum migrasi atau penggantian rilis.

Dokumentasi lengkap tersedia pada direktori `docs/`, terutama `DEPLOYMENT.md`, `SECURITY.md`, dan `SCHEMA.md`.
