## Bagian 2 - Laravel Dasar

Implementasi CRUD `companies` dan `employees` menggunakan Laravel 11 + `laravel/ui` (Bootstrap 5), Repository Pattern, Service layer, upload logo private, export PDF, dan import Excel all-or-nothing.

## Requirement / Prerequisites

Sebelum menjalankan project ini, pastikan dependency berikut tersedia:

### System

- PHP `8.2` atau lebih baru
- Composer
- Node.js dan npm
- Database: SQlite
- `wkhtmltopdf` binary untuk fitur export PDF

### PHP Extensions

Extension yang perlu aktif agar Laravel, upload image, dan import Excel berjalan normal:

- `bcmath`
- `ctype`
- `fileinfo`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `tokenizer`
- `xml`
- `zip`
- `gd`

### Composer Packages Utama

Dependency utama yang digunakan project ini:

- `laravel/framework:^11.31`
- `laravel/ui:^4.6`
- `barryvdh/laravel-snappy:^1.0`
- `maatwebsite/excel:^3.1`

### Frontend / Build Tools

- `vite`
- `bootstrap 5`
- `sass`

Catatan:

- `select2`, `jQuery`, dan Material Design Icons dimuat via CDN di layout aplikasi
- Jika hanya ingin reviewer menjalankan aplikasi, dependency frontend cukup di-install lewat `npm install`
- Jika hanya ingin melihat source code tanpa compile asset ulang, dependency npm tetap saya sarankan agar environment konsisten

## Akun Login

- Email: `admin@transisi.id`
- Password: `transisi`

## Fitur yang Diimplementasikan

- CRUD `companies` dan `employees`
- Repository Pattern + interface binding di `AppServiceProvider`
- Service layer untuk orkestrasi business logic
- Validasi dengan Form Request
- Route model binding + resource route `except(['show'])`
- Pagination list 5 data per halaman
- Select2 AJAX untuk dropdown company di form employee, dengan pagination 10 data per request
- Upload logo company ke `storage/app/company`
- Logo di-serve lewat route authenticated `companies.logo`
- Export PDF employee per company menggunakan `barryvdh/laravel-snappy`
- Import Excel employee menggunakan `maatwebsite/excel`
- Import Excel bersifat all-or-nothing dengan validasi dua tahap
- Unique constraint untuk `companies.name`, `companies.email`, dan `employees.email`
- Preserve old input saat validasi gagal, termasuk preserve file logo company melalui temporary storage

## Asumsi yang Diambil

Sesuai petunjuk pengerjaan yang mengizinkan asumsi, berikut keputusan yang diambil:

### Database

- `companies.name` dibuat unique untuk menjaga konsistensi data company
- `companies.email` dibuat unique karena email company diperlakukan sebagai identitas kontak utama
- `employees.email` dibuat unique karena email adalah identifier alami untuk employee
- Tidak menggunakan soft delete karena tidak ada requirement restore data

### Authentication

- Hanya ada 1 role, yaitu admin
- Public registration di-disable karena requirement hanya menyebut autentikasi administrator

### File Handling

- Logo disimpan di `storage/app/company`, bukan public storage, sesuai soal
- Logo diakses lewat route authenticated `companies.logo` menggunakan `Storage::disk('local')->response()`
- Saat validasi form company gagal, file logo yang valid disimpan sementara di `storage/app/tmp` agar user tidak perlu upload ulang
- Temporary logo akan dipindah ke folder `company/` saat submit berhasil
- Disediakan command cleanup `tmp:cleanup-logo` dan scheduler hourly untuk membersihkan file tmp yang sudah stale

### Import Excel

- Strategi import menggunakan all-or-nothing: jika ada satu baris gagal validasi, seluruh import dibatalkan
- Validasi duplicate email dilakukan terhadap dua sumber:
  - duplicate di dalam file yang sama
  - duplicate dengan data yang sudah ada di database
- Chunk insert dan batch insert keduanya menggunakan size `10` sesuai requirement

## Setup Singkat

1. Install dependency

```bash
composer install
npm install
```

2. Copy environment dan generate key

```bash
copy .env.example .env
php artisan key:generate
```

3. Configure database di `.env`, lalu jalankan migration + seed

```bash
php artisan migrate --seed
```

4. Jalankan aplikasi

```bash
php artisan serve
npm run dev
```

## PDF Export

Fitur export PDF menggunakan `wkhtmltopdf`.

Default path yang digunakan di project ini:

```text
C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe
```

Pastikan `.env` berisi path binary yang sesuai:

```env
WKHTML_PDF_BINARY="C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe"
```

Catatan:

- Logo company di-embed sebagai base64 di template PDF agar tetap bisa muncul walaupun file logo diakses via route authenticated

## Import Excel

Contoh file sample sudah disiapkan di root project:

- `sample-employees.xlsx`
- `sample-employees-error.xlsx`

Generate ulang sample file bila diperlukan:

```bash
php artisan sample:employees-excel --type=valid
php artisan sample:employees-excel --type=error
```

## Preserve Logo Saat Validasi Gagal

Requirement soal menyebut old input tidak hilang saat validasi gagal. Untuk field file, browser tidak mengizinkan value file di-restore langsung. Karena itu implementasi dilakukan dengan strategi berikut:

1. Jika user upload logo yang valid tetapi field lain gagal validasi, file logo disimpan sementara ke `storage/app/tmp`
2. Form akan menampilkan status `Logo siap dipakai`
3. User bisa submit ulang tanpa upload ulang file
4. Jika user memilih file baru, tmp logo lama akan dibersihkan
5. Saat submit berhasil, tmp logo dipindahkan ke `storage/app/company`

Cleanup tmp logo:

```bash
php artisan tmp:cleanup-logo
php artisan tmp:cleanup-logo --minutes=30
php artisan tmp:cleanup-logo --dry-run
```

Scheduler sudah didaftarkan di `routes/console.php` untuk menjalankan cleanup setiap jam.
