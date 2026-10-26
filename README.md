# TANGSEL LIA

**Satu tempat untuk menemukan informasi dan layanan Tangerang Selatan.**

Platform informasi lokal yang menghimpun berita kota, direktori tempat, dan
lowongan kerja/magang ke dalam satu pengalaman pencarian. Dibangun sebagai
Tugas Akhir Program Studi Teknik Informatika.

---

## Fitur

| Modul | Isi | Sumber data |
|---|---|---|
| **Beranda** | Ringkasan berita, lowongan terbaru, dan direktori | Agregasi lintas modul |
| **Berita** | Warta kota dengan ringkasan otomatis bertanda "Diringkas AI" | RSS: Google News, Bing News, media lokal |
| **Lowongan Kerja & Magang** | Lowongan terkurasi dengan filter jenis, bidang pekerjaan, dan kecamatan | RSS job board + media lokal, lalu kurasi administrator |
| **Jelajah** | Direktori kuliner, fasilitas olahraga, dan layanan kesehatan | OpenStreetMap (Overpass API) + Google Places |
| **Ulasan** | Rating dan komentar warga pada entri direktori | Input pengguna |
| **Dashboard Admin** | Kurasi lowongan, pantau status sumber, picu agregasi manual | — |

### Alur kurasi lowongan

```
feed RSS → saring wilayah → dedup → ekstrak → status "baru"
                                                      │
                                    ┌─────────────────┴─────────────────┐
                                    ▼                                   ▼
                            "Tayang" (+30 hari)                    "Ditolak"
                                    │
                                    ▼
                            "Kedaluwarsa" (otomatis)
```

Penyaringan wilayah memakai penanda teks (bukan pelacakan lokasi pengguna),
dan penentuan jenis lowongan bersifat deterministik dari peta kata kunci —
bukan hasil tebakan model bahasa.

---

## Stack

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.4, Laravel 13.17, Laravel Sanctum 4.3 |
| Basis data | MySQL 8 (`utf8mb4`) |
| Frontend | Next.js 16.3, React 19.2, TypeScript 5 |
| Styling | Tailwind CSS 4 |
| AI | Google Gemini (`gemini-3.1-flash-lite`) — opsional, ada fallback |
| Peta & tempat | OpenStreetMap (Overpass API), Google Places API |
| Pengujian | PHPUnit 12, ESLint 9 |

---

## Struktur Repo

```
tangselia/
├── tangsel-api/     Backend Laravel: REST API, database, service agregasi
├── tangsel-app/     Frontend Next.js: halaman publik + dashboard admin
├── scraper/         Skrip Python pengumpul data awal (hasil dalam JSON)
└── docs/            Catatan teknis, rencana kerja, dan laporan Tugas Akhir
```

---

## Cara Menjalankan

### Prasyarat

- PHP 8.3+ dengan ekstensi `pdo_mysql`, `mbstring`, `gd`, `openssl`
- Composer
- MySQL 8
- Node.js 20+

### 1. Backend

```bash
cd tangsel-api
composer install
cp .env.example .env
php artisan key:generate
```

Sesuaikan `.env`:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tangselia
DB_USERNAME=root
DB_PASSWORD=

# Opsional — tanpa ini fitur AI otomatis nonaktif, aplikasi tetap jalan
GEMINI_API_KEY=
GEMINI_MODEL=gemini-3.1-flash-lite

# Opsional — tanpa ini rating & foto Google tidak muncul
GOOGLE_MAPS_API_KEY=
```

Lalu siapkan database:

```bash
php artisan migrate --seed
php artisan serve
```

API berjalan di `http://localhost:8000/api`.

### 2. Frontend

```bash
cd tangsel-app
npm install
```

Buat `.env.local`:

```dotenv
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

```bash
npm run dev
```

Aplikasi berjalan di `http://localhost:3000`.

> Gunakan `npm run dev`, bukan `npm start`. Pada `npm start` cookie autentikasi
> ditandai `secure` sehingga browser menolaknya saat diakses lewat HTTP biasa.

### 3. Akun demo

| Peran | Email | Password |
|---|---|---|
| Administrator | `admin@tangselia.id` | `password` |
| Warga | `warga@tangselia.id` | `password` |

---

## Perintah Pengumpulan Data

```bash
cd tangsel-api

# Berita: ambil dari seluruh sumber RSS aktif
php artisan berita:fetch

# Berita: batasi ke satu sumber
php artisan berita:fetch --source="Google News"

# Lowongan: ambil dari seluruh sumber RSS lowongan
php artisan lowongan:fetch

# Lowongan: tandai yang masa tayangnya habis
php artisan lowongan:expire

# Direktori: impor dari OpenStreetMap
php artisan info:fetch --fitur=tempat

# Direktori: sinkronkan rating & foto dari Google Places
php artisan places:sync --fitur=tempat
php artisan deskripsi:ai --fitur=tempat
php artisan wiki:sync
```

Setiap item baru dari `lowongan:fetch` masuk dengan status `baru` dan menunggu
kurasi administrator. Item berita langsung berstatus `published`.

---

## Pengujian

```bash
# Backend
cd tangsel-api
php artisan test --compact

# Frontend
cd tangsel-app
npm run lint
npm run build
```

---

## Ringkasan Endpoint

| Method | Endpoint | Keterangan |
|---|---|---|
| `POST` | `/api/auth/register`, `/api/auth/login`, `/api/auth/logout` | Autentikasi Sanctum |
| `GET` | `/api/auth/me` | Profil pengguna aktif |
| `GET` | `/api/berita`, `/api/berita/terbaru`, `/api/berita/meta`, `/api/berita/{slug}` | Berita publik |
| `GET` | `/api/lowongan`, `/api/lowongan/terbaru`, `/api/lowongan/meta`, `/api/lowongan/{slug}` | Lowongan publik |
| `GET` | `/api/{fitur}`, `/api/{fitur}/meta`, `/api/{fitur}/{slug}` | Direktori (`kuliner`, `olahraga`, `kesehatan`) |
| `GET` | `/api/{fitur}/{slug}/google`, `/foto`, `/ulasan`, `/terdekat` | Pengayaan & interaksi direktori |
| `GET` | `/api/admin/lowongan`, `/api/admin/lowongan/meta` | Daftar kurasi (admin) |
| `PUT` | `/api/admin/lowongan/{id}` | Koreksi field & transisi status (admin) |
| `DELETE` | `/api/admin/lowongan/{id}` | Hapus lowongan (admin) |
| `POST` | `/api/admin/berita/refresh` | Picu agregasi berita manual (admin) |

---

## Catatan Keamanan

- Token Sanctum disimpan di **cookie httpOnly** melalui pola *Backend For
  Frontend*. Token tidak pernah dapat dibaca JavaScript di browser.
- Seluruh endpoint admin dilindungi middleware `auth:sanctum` + `role:admin`,
  dan diteruskan frontend lewat proxy Route Handler.
- Berkas `.env` tidak pernah masuk repositori; hanya `.env.example` yang disertakan.
- Isi artikel berita tidak disimpan utuh. Sistem menghormati `robots.txt`
  penerbit dan hanya menyimpan cuplikan beserta tautan ke artikel asli.

---

## Atribusi Data

- Peta dan data tempat: © kontributor [OpenStreetMap](https://www.openstreetmap.org/copyright), lisensi ODbL.
- Rating dan foto tempat: Google Places API.
- Berita dan lowongan: hak cipta masing-masing penerbit; sistem hanya menampilkan
  ringkasan beserta tautan ke sumber asli.
