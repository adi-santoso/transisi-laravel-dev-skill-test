## Asumsi yang Diambil

Sesuai petunjuk pengerjaan yang mengizinkan asumsi, berikut keputusan
yang saya ambil:

### Database
- `companies.name` dan `companies.email` dibuat unique untuk integrity
- `employees.email` unique karena email = identifier alami untuk person
- Tidak menggunakan softDelete karena tidak ada requirement restore

### Authentication
- Hanya 1 role (admin), tidak menggunakan sistem role kompleks
- Public registration di-disable karena hanya admin yang dimention

### File handling
- Logo di-serve via route khusus dengan auth middleware (sesuai spec
  yang minta simpan di `storage/app/company`)

### Import Excel
- Strategi all-or-nothing: jika ada satu baris yang gagal validasi,
  seluruh import dibatalkan untuk menjaga konsistensi data
- Duplicate detection: email tidak boleh duplicate dalam file maupun
  dengan email yang sudah ada di DB
