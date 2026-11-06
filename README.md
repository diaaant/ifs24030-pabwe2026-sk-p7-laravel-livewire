# Aplikasi Inventaris Stok Barang (Laravel 13 + Livewire 4, Feature-First)

Studi kasus PABWE 2026 P7. Stack: Laravel 13, Livewire 4, MySQL, PhpSpreadsheet, OpenAI API.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# sesuaikan DB_* dan OPENAI_API_KEY di .env
php artisan migrate:fresh --seed
php artisan storage:link
npm install --ignore-scripts && npm run build
php artisan serve
```

Akun seeder: `admin / admin` (admin) dan `member / member` (member, sudah berisi contoh stok).

Ekstensi PHP yang dibutuhkan: `gd`, `zip`, `mbstring`, `pdo_mysql`.

## Pengujian

```bash
composer test            # php artisan test (SQLite in-memory)
composer test:coverage   # PHPUnit + coverage + gate 100% (butuh Xdebug/PCOV)
composer audit
```

## Catatan desain

- Status stok dihitung otomatis: `quantity == 0` → out_of_stock, `quantity <= min_stock` → low_stock, selain itu available.
- `AiClientInterface` diimplementasikan `OpenAiStockGeneratorService` (di-bind di `AppServiceProvider`); di test cukup `createStub(AiClientInterface::class)`.
- Middleware: `role` (EnsureRole), `owns.stock` (EnsureStockOwnership). Rate limiter `1000-per-second`, `FORCE_HTTPS` di `.env`.
- Admin hanya read-only terhadap stok user lain (tidak ada aksi ubah/hapus di `AdminUserStockList`, dan `StockPolicy` menolak update/delete/adjust untuk admin).
- Jangan commit `.env` maupun artefak uji (`coverage.*`, `junit.xml`, dst. sudah di `.gitignore`).
