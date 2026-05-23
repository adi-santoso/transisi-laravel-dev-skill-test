# Transisi - Tes Laravel Developer 2026

Repository ini berisi hasil pengerjaan tes dengan dua bagian:

- `bagian_1/` - PHP Dasar
- `bagian_2/` - Laravel Dasar

Selain source code, repository ini juga menyertakan file ringkasan submission:

- `submission_ringkasan.html`
- `submission_ringkasan.pdf`

## Struktur Repository

### `bagian_1/`

Berisi jawaban untuk soal PHP Dasar:

- Soal 1: rata-rata, 7 nilai tertinggi, 7 nilai terendah
- Soal 2: hitung jumlah huruf kecil
- Soal 3: unigram, bigram, trigram
- Soal 4: pola tabel angka
- Soal 5: fungsi enkripsi

Lihat panduan menjalankan di:

- `bagian_1/README.md`

## Cara Menjalankan Bagian 1

Masuk ke folder `bagian_1`, lalu jalankan built-in server PHP:

```bash
php -S localhost:8000
```

Setelah itu buka browser:

```text
http://localhost:8000
```

### `bagian_2/`

Berisi aplikasi Laravel Dasar untuk mengelola data `companies` dan `employees`.

Fitur utama:

- autentikasi admin
- CRUD companies dan employees
- upload logo company private
- export PDF employee per company
- import Excel employee all-or-nothing
- select2 AJAX dropdown company
- preserve old input saat validasi gagal

Lihat detail setup dan requirement di:

- `bagian_2/README.md`

## Cara Menjalankan Bagian 2

Masuk ke folder `bagian_2`, lalu jalankan:

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm run dev
```

## Akun Login Bagian 2

- Email: `admin@transisi.id`
- Password: `transisi`

## Requirement Ringkas Bagian 2

- PHP 8.2+
- Composer
- Node.js dan npm
- MySQL / PostgreSQL
- `wkhtmltopdf` untuk export PDF

## Ringkasan Submission

Untuk memudahkan reviewer, ringkasan submission tersedia dalam dua format di root repository:

- HTML: `submission_ringkasan.html`
- PDF: `submission_ringkasan.pdf`
