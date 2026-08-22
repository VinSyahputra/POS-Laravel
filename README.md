# POS Food Court App — Laravel + Alpine.js

Aplikasi Point of Sale (POS) untuk food court: **dashboard tunggal tanpa login** dengan modul Kasir (transaksi + cetak nota Bluetooth), manajemen menu, riwayat transaksi harian, dan pengaturan printer thermal. Berjalan penuh di **Docker**.

| Layer | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.4-FPM) |
| Frontend Interaktif | Alpine.js 3 |
| Styling | Tailwind CSS 4 + Vite 8 |
| Database | MySQL 8.0 (Docker) |
| Web Server | Nginx (port 8000) |
| Printer | Web Bluetooth API → thermal ESC/POS (58mm/80mm) |

## Fitur

- **Kasir** — grid menu + filter kategori, keranjang, diskon/pajak opsional, pembayaran cash + kembalian, nomor nota harian (`NOTA-YYYYMMDD-NNNN`), cetak nota via Bluetooth.
- **Food Court Menu** — CRUD kategori & menu (nama, kategori, harga; tanpa manajemen stok).
- **Riwayat Harian** — daftar & detail transaksi per tanggal, total penjualan, cetak ulang nota.
- **Pengaturan Printer** — pairing printer Bluetooth, pilih lebar kertas 58mm/80mm, deteksi dukungan browser.
- Tanpa login/role — semua fitur langsung diakses dari dashboard.

## Setup dari Nol

Prasyarat host: [Docker](https://docs.docker.com/get-docker/) + Node.js (untuk build asset).

```bash
# 1. Environment file Laravel (aplikasi ada di src/)
cd src
cp .env.example .env
# Pastikan blok DB: DB_CONNECTION=mysql, DB_HOST=db, DB_PORT=3306,
# DB_DATABASE=laravel, DB_USERNAME=laravel, DB_PASSWORD=root
# dan OUTLET_NAME="Nama Outlet Anda" (muncul di header nota)
cd ..

# 2. Jalankan seluruh stack (app, nginx, db, redis)
docker compose up -d --build

# 3. Install dependency PHP & generate key
docker compose exec app composer install
docker compose exec app php artisan key:generate

# 4. Migrasi + seed data contoh (3 kategori, 9 menu, printer default 80mm)
docker compose exec app php artisan migrate --seed

# 5. Build asset frontend (di host, dalam folder src/)
cd src && npm install && npm run build && cd ..

# 6. Permission storage (jika muncul error 500 "tempnam")
docker compose exec app chmod -R 777 /var/www/storage /var/www/bootstrap/cache
```

Aplikasi: **http://localhost:8000**

> Port MySQL di host dipetakan ke **33061** (3306 lokal sering bentrok). Laravel di dalam container tetap memakai `db:3306`.

## Perintah Berguna

| Aksi | Perintah |
|---|---|
| Stop stack | `docker compose down` |
| Logs | `docker compose logs -f app` |
| Artisan | `docker compose exec app php artisan <cmd>` |
| Composer | `docker compose exec app composer <cmd>` |
| Build ulang asset | `cd src && npm run build` |
| Jalankan test (Pest) | `docker compose exec app php artisan test --compact` |
| Reset DB + seed | `docker compose exec app php artisan migrate:fresh --seed` |

## Cetak Nota via Bluetooth (Web Bluetooth)

1. Buka tab **Printer** → klik **Pair Printer Bluetooth** → pilih printer thermal (Chrome akan mengingat izin perangkat, tidak perlu pairing ulang tiap transaksi).
2. Pilih lebar kertas sesuai printer (58mm / 80mm) → Simpan.
3. Setelah transaksi tersimpan, klik **Cetak Nota**. Gagal cetak (printer mati / tidak didukung) akan menampilkan **preview nota di layar** + opsi **Cetak Ulang**.

**Batasan penting:**
- Web Bluetooth hanya berjalan di browser **berbasis Chromium** (Chrome, Edge). Tidak didukung Safari/Firefox.
- Konteks harus **HTTPS atau localhost**.
- Protokol ESC/POS standar; jika merk printer tertentu tidak kompatibel, sesuaikan UUID service di `src/resources/js/bluetooth.js`.

## Keamanan (Penting — Tanpa Login)

Aplikasi ini **sengaja tanpa autentikasi**. Siapa pun yang bisa mengakses URL dapat mengubah menu & transaksi. Karena itu:

- Gunakan hanya di **jaringan internal outlet** (perangkat kasir khusus), **bukan** di internet terbuka.
- Untuk akses jarak jauh, gunakan **VPN** — atau batasi IP di level web server/firewall.
- CSRF protection Laravel tetap aktif untuk semua operasi tulis.

## Struktur Penting

```
docker-compose.yml        # stack: app (php-fpm), nginx :8000, mysql 8, redis
src/                      # aplikasi Laravel
├── app/Http/Controllers  # Category, Menu, Transaction, PrinterSetting
├── app/Models            # Category, Menu, Transaction, TransactionItem, PrinterSetting
├── resources/js/pos.js   # komponen Alpine (menuManager, cashier, history, printerSettings)
├── resources/js/escpos.js# builder nota ESC/POS + preview teks
├── resources/js/bluetooth.js # koneksi Web Bluetooth + chunked write
└── tests/Feature         # Pest: 29 test (CRUD, transaksi, kalkulasi, dashboard)
```
