# Catatan Teknis — Direktori Tempat (Kuliner, Olahraga, Kesehatan)

> Integrasi impor otomatis data tempat dari OpenStreetMap dan penyajian rating/foto
> secara live dari Google Places API (New).
>
> **Status:** backend selesai diimplementasikan (impor OSM, live Google, command, tes).
> Frontend admin/peta belum dikerjakan.
> **Proyek:** Tugas Akhir — TANGSEL LIA.

## Catatan Eksekusi

- **12 Okt 2026** — `php artisan migrate` berhasil: kolom `osm_id`, `google_place_id`,
  `sumber`, `jam_buka_teks` ditambahkan ke `tempat`, `fasilitas_olahraga`,
  `layanan_kesehatan` (MySQL `tangselia`, port 3306).
- **12 Okt 2026** — `php artisan places:sync` (impor nyata dari Overpass):

  | Fitur | Dibuat | Dilewati | Gagal |
  |---|---|---|---|
  | Kuliner | 783 | 20 | 0 |
  | Olahraga | 33 | 356 | 0 |
  | Kesehatan | 215 | 4 | 0 |

  Total **1.031 tempat asli** masuk DB (+ 6 data contoh seeder dipertahankan).
- **Catatan:** mayoritas elemen olahraga dilewati karena `swimming_pool` di OSM
  **tidak memiliki `name`** (kolam apartemen/hotel privat). Kalau perlu lebih banyak,
  filter nama bisa dilonggarkan atau kategori ditambah.
- **Google live belum aktif** (`GOOGLE_MAPS_API_KEY` kosong) — tinggal jalankan
  `places:match` begitu key dan billing siap.
- **12 Okt 2026** — Frontend `tangsel-app` di-*wiring* ke API (Tahap 4):
  `/kuliner` + `/tempat/[slug]`, `/olahraga` + `/olahraga/[slug]`,
  `/kesehatan` + `/kesehatan/[slug]`, `/berita` + `/berita/[slug]` — semuanya
  ambil data nyata via `src/lib/api.ts` (`apiFetch`, `cache: "no-store"`).
  Komponen bersama `src/components/direktori/DetailDirektori.tsx` +
  `DirektoriListView.tsx` dipakai tiga fitur direktori; param `slug` detail
  kini benar-benar dibaca (sebelumnya diabaikan). Route `/agenda` dihapus.
- **12 Okt 2026** — Laporan + beranda: `/laporan` (feed + tab "Laporan Saya"),
  `/laporan/buat` (form kategori/GPS/maks 3 foto → proxy BFF `/api/laporan`),
  beranda `/` (Kabar dari `GET /berita/terbaru`, "Pantau Aduan Sekitar" dari
  `GET /laporan`). Backend ditambah `GET /laporan/meta` (opsi form) dan
  `GET /admin/laporan` (daftar moderasi). `GET /laporan?mine=1` diperbaiki agar
  membaca Bearer token via `auth('sanctum')->user()` — sebelumnya
  `$request->user()` selalu `null` karena route publik tidak memakai middleware
  `auth:sanctum` (tertutup tes baru `test_feed_mine_menerima_bearer_token`).
- **12 Okt 2026** — **Keputusan auth frontend:** pakai **BFF route Next**
  (`/api/auth/login|register|logout`, `/api/laporan`, `/api/admin/[...path]`)
  dengan token Sanctum disimpan di cookie **httpOnly** `tangsel_token`; request
  server-side menyisipkan `Authorization: Bearer`. Alasan: `config/cors.php`
  `supports_credentials => false`, jadi cookie stateful Sanctum tidak bisa
  dipakai lintas origin. Token tidak pernah terekspos ke JavaScript browser.
  `/dashboard/admin` dijaga `role: admin` (warga dialihkan ke `/dashboard/user`);
  `/login`, `/laporan/buat`, dan `/dashboard/*` butuh sesi.
- **12 Okt 2026** — Verifikasi: `npx tsc --noEmit` + `npm run build` bersih;
  alur login warga/admin, submit laporan (201), verifikasi + ubah status admin
  (200), guard admin (307), dan proxy admin (403 untuk non-admin) diuji live.
  Backend `php artisan test --compact` → **77 tes hijau**.
- **12 Okt 2026** — Hapus route mock lama `/tempat/coffee-shop` + `/tempat/olahraga`.
  Keduanya bukan bagian peta route final dan, karena segmen statis menang atas
  `/tempat/[slug]`, sempat menutup halaman detail untuk slug `coffee-shop`/`olahraga`.
  Verifikasi: kedua URL kini `404`, `/tempat/[slug]` tetap `200`, `npm run lint` bersih
  (0 warning, turun dari 11), build 17 route.

## Daftar Isi

1. Latar Belakang
2. Keputusan Desain
3. Batasan Penting (ToS Google)
4. Sumber Data
5. Pemetaan Tag OpenStreetMap
6. Perubahan Database
7. Alur Kerja
8. Kebutuhan Endpoint API
9. Artisan Command
10. Risiko dan Mitigasi
11. Batasan Cakupan (Belum Dikerjakan)
12. Generate Deskripsi & Perlindungan Data Admin
13. Berkas Implementasi

---

## 1. Latar Belakang

Data direktori tempat pada platform TANGSEL LIA — kuliner (coffee shop/warkop/restoran),
fasilitas olahraga, dan layanan kesehatan — saat ini hanya dapat diisi secara manual
melalui CRUD admin. Cara ini dianggap tidak efisien karena:

- Membutuhkan input satu per satu oleh administrator.
- Data (terutama lokasi dan nama) rawan tidak lengkap dan sulit diperbarui.

Fitur ini menambahkan **impor otomatis** data tempat dari OpenStreetMap, serta
penyajian **rating dan foto** secara *live* dari Google Places API (New) tanpa
menyimpan data tersebut ke basis data.

## 2. Keputusan Desain

| Aspek | Keputusan | Alasan |
|-------|-----------|--------|
| Data inti (nama, alamat, kategori, latitude/longitude, jam buka, kecamatan) | **OpenStreetMap** — disimpan ke DB | Berlisensi ODbL, boleh disimpan |
| Rating dan foto | **Google Places API (New)** — *live*, tidak disimpan | Mengikuti kebijakan Google; hanya `place_id` yang boleh disimpan |
| Menu | **Ditunda** | Google Places tidak menyediakan data menu |
| Scraping Google Maps | **Ditolak** | Melanggar ToS dan berisiko blokir |
| API key Google | Via `.env` (`GOOGLE_MAPS_API_KEY`) | Tidak hardcode di source code |
| Deduplikasi | Kolom `osm_id` (OSM) dan `google_place_id` (Google) | Mencegah data duplikat saat sinkronisasi |

## 3. Batasan Penting (ToS Google)

Kebijakan resmi Google Places API menyatakan bahwa konten Places **tidak boleh**
di-*pre-fetch*, di-*cache*, atau disimpan, kecuali `place_id`.

| Data | Boleh disimpan di DB? | Catatan |
|------|-----------------------|---------|
| `place_id` | Boleh | Dikecualikan dari larangan caching |
| `latitude` / `longitude` | Terbatas | Hanya boleh di-cache 30 hari |
| Nama, alamat, rating, foto, jam buka, telepon | Tidak | Harus diambil *live* setiap ditampilkan |

Kewajiban atribusi:

- Wajib menampilkan atribusi **"Google Maps"** saat menampilkan konten Places.
- Foto/review wajib menampilkan atribusi penulis dan tautan ke sumber di Google Maps (`googleMapsUri`).
- Aplikasi wajib menyediakan Terms of Use dan Privacy Policy.

Konsekuensi teknis:

- Data Google **tidak masuk ke DB**; hanya `google_place_id` yang disimpan.
- Endpoint backend berperan sebagai *proxy* ke Google agar API key tetap di server.

## 4. Sumber Data

### 4.1 OpenStreetMap (Overpass API)

- Query dilakukan per kategori pada **bounding box** Kota Tangerang Selatan
  (`-6.3622632, 106.6366233, -6.2289594, 106.7800127`).
- **Beberapa endpoint Overpass dicoba berurutan** (`overpass-api.de`, mirror
  `maps.mail.ru`, `overpass.private.coffee`) dengan percobaan ulang, karena
  server publik sering menolak (HTTP 504) saat sibuk.
- `latitude`/`longitude` diambil dari titik (`node`) atau pusat geometri (`way`/`relation`).
- **Kecamatan ditentukan lewat centroid terdekat** (rumus *haversine*) karena
  batas administratif kecamatan tidak tersedia di OSM (hasil verifikasi Nominatim
  hanya mengembalikan *node* kota, bukan *polygon* kecamatan).
- Field yang dipakai: `name`, `addr:*`, `opening_hours`, `phone`, koordinat.

### 4.2 Google Places API (New)

- **Text Search (New)** — untuk mencocokkan tempat OSM dengan `google_place_id`.
- **Place Details (New)** — untuk mengambil rating, jumlah rating, jam buka, foto,
  telepon, dan `googleMapsUri` secara *live*.
- **Places Photo** — foto diakses melalui endpoint proxy backend agar API key tidak
  terekspos ke frontend.

## 5. Pemetaan Tag OpenStreetMap

| Entitas | Tag OSM | Kolom `jenis` |
|---------|---------|---------------|
| `tempat` | `amenity=cafe` | `kafe` |
| `tempat` | `amenity=restaurant` | `restoran` |
| `tempat` | `amenity=fast_food` | `warkop` |
| `fasilitas_olahraga` | `leisure=fitness_centre` | `gym` |
| `fasilitas_olahraga` | `leisure=swimming_pool` | `kolam_renang` |
| `fasilitas_olahraga` | `leisure=pitch` + `sport=futsal` | `lapangan_futsal` |
| `fasilitas_olahraga` | `leisure=pitch` + `sport=basketball` | `lapangan_basket` |
| `fasilitas_olahraga` | `leisure=sports_centre` + `sport=badminton` | `gor_bulutangkis` |
| `layanan_kesehatan` | `amenity=hospital` | `rumah_sakit` |
| `layanan_kesehatan` | `amenity=pharmacy` | `apotek` |
| `layanan_kesehatan` | `amenity=clinic` | `klinik` / `puskesmas` (cek prefix nama) |
| `layanan_kesehatan` | `amenity=doctors` | `klinik` |

## 6. Perubahan Database

Tambah kolom berikut pada tabel `tempat`, `fasilitas_olahraga`, dan `layanan_kesehatan`:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `osm_id` | string, nullable, **unique** | ID elemen OSM (`node/123`, `way/456`) untuk dedup |
| `google_place_id` | string, nullable, **unique** | ID tempat Google (boleh disimpan) |
| `sumber` | enum(`manual`,`osm`), default `manual` | Asal data |
| `jam_buka_teks` | string, nullable | `opening_hours` mentah dari OSM |

Pemetaan field saat impor:

| Kolom kita | Sumber OSM |
|------------|------------|
| `nama` | `name` |
| `alamat` | `addr:full` → `addr:street` + `addr:housenumber` → fallback nama kecamatan |
| `latitude` / `longitude` | koordinat elemen |
| `jam_buka` / `jam_tutup` | hasil parsing `opening_hours` pola sederhana (gagal → `null`) |
| `jam_buka_teks` | `opening_hours` (mentah) |
| `telepon` | `phone` (khusus kesehatan) |
| `layanan_24_jam` | `opening_hours` memuat `24/7` (khusus kesehatan) |
| `fasilitas` | tag positif (Wi-Fi, AC, akses kursi roda, dll.) |
| `deskripsi` | dihasilkan otomatis dari tag OSM (lihat §12) |
| `rating` / `rating_kount` | `null` (OSM tidak menyediakan rating) |

> Entri yang `osm_id`-nya sudah ada **dilewati** saat impor ulang, sehingga
> `deskripsi` (dan kolom lain) yang pernah disunting admin **tidak pernah tertimpa**.

## 7. Alur Kerja

```
ADMIN ──klik "Impor dari Peta"──► POST /api/admin/{fitur}/import
                                      │
                                      ▼
                              OsmImporter (Overpass API)
                              - query bbox Tangsel per kategori
                              - map tag OSM → kolom kita
                              - tentukan kecamatan (centroid terdekat)
                              - generate deskripsi (Opsi B)
                              - dedup by osm_id (entri lama dilewati)
                                      │
                                      ▼
                                Database (boleh disimpan ✅)

USER ──buka detail──► GET /api/{fitur}/{slug}          (data dari DB)
                      GET /api/{fitur}/{slug}/google   ─► GooglePlacesLookup (LIVE)
                      GET /api/{fitur}/{slug}/foto      - rating, jam buka, foto
                                                         - TIDAK disimpan (patuh ToS ✅)
                                                         - sertakan atribusi
```

## 8. Kebutuhan Endpoint API

**Publik**

| Method | Path | Keterangan |
|--------|------|------------|
| `GET` | `/api/{fitur}` | Daftar (sudah ada) |
| `GET` | `/api/{fitur}/{slug}` | Detail (sudah ada) |
| `GET` | `/api/{fitur}/{slug}/google` | Rating/foto/jam *live* + atribusi (`data: null` bila tak ada kecocokan/API key kosong) |
| `GET` | `/api/{fitur}/{slug}/foto?photo=<name>&w=<px>` | Proxy foto Google (key di server, wajib milik `google_place_id` tempat tersebut) |

`{fitur}` = `kuliner`, `olahraga`, `kesehatan`. Dua rute terakhir memakai prefix
`{fitur}` dengan constraint `kuliner|olahraga|kesehatan`.

**Admin** (`auth:sanctum` + `role:admin`)

| Method | Path | Body | Respons |
|--------|------|------|---------|
| `POST` | `/api/admin/{fitur}/import` | `{jenis?}` | `{message, data:{dibuat, dilewati, gagal}}` |

`{fitur}` = `tempat`, `olahraga`, `kesehatan` (memetakan ke model masing-masing).

## 9. Artisan Command

| Command | Fungsi |
|---------|--------|
| `php artisan places:sync {--fitur=}` | Menjalankan impor/sinkronisasi dari OSM (entri lama tidak ditimpa) |
| `php artisan places:match {--fitur=}` | Backfill `google_place_id` (hanya `place_id` yang disimpan); dilewati bila `GOOGLE_MAPS_API_KEY` kosong |

`{--fitur=}` menerima `tempat`, `olahraga`, atau `kesehatan` (kosong = semua).

## 10. Risiko dan Mitigasi

| Risiko | Mitigasi |
|--------|----------|
| Overpass API down / rate limit | Beberapa endpoint cadangan + percobaan ulang + `try/catch` per fitur |
| Google error / kuota habis | Detail tetap tampil, rating/foto kosong (tanpa error 500) |
| Alamat OSM kosong | Fallback ke nama kecamatan |
| Data duplikat | Unique `osm_id` + `google_place_id` |
| Kecamatan salah (centroid) | Perkiraan cukup untuk Tangsel; bisa diperbaiki manual oleh admin |
| `opening_hours` kompleks | Disimpan mentah di `jam_buka_teks`; parsing sederhana saja |

## 11. Batasan Cakupan (Belum Dikerjakan)

- Tombol "Impor dari Peta" di dashboard admin (endpoint `POST /admin/{fitur}/import` sudah ada).
- Peta Leaflet memakai `latitude`/`longitude` + panel rating/foto Google + atribusi.
- Parsing `opening_hours` untuk pola kompleks.
- Fitur menu (kolom `menu` belum diisi; Google tidak menyediakan data menu).

## 12. Generate Deskripsi & Perlindungan Data Admin

Karena OSM tidak menyediakan deskripsi, deskripsi **dihasilkan otomatis** dari
tag OSM (Opsi B) saat baris dibuat:

1. "{Nama} merupakan salah satu {Kuliner/Olahraga/Kesehatan} di Kecamatan {Nama}, Kota Tangerang Selatan."
2. "Berlokasi di {addr:street}." (bila ada)
3. "Fasilitas yang tersedia antara lain {daftar fasilitas}." (bila ada)
4. "Jam operasional: {opening_hours}." (bila ada)
5. "Data ini diambil otomatis dari OpenStreetMap dan dapat diperbarui oleh pengelola."

Aturan penting: **deskripsi hanya di-*set* saat pembuatan.** Saat `places:sync`
dijalankan ulang, entri dengan `osm_id` yang sama dilewati, sehingga deskripsi
yang sudah disunting admin tidak pernah tertimpa.

## 13. Berkas Implementasi

| Berkas | Peran |
|--------|-------|
| `config/osm.php` | Endpoint Overpass, bbox, centroid kecamatan, peta tag→`jenis`/fasilitas |
| `config/places.php` | Kunci & field mask Google Places (New) |
| `app/Services/OsmImporter.php` | Impor OSM (query, pemetaan, kecamatan, generate deskripsi) |
| `app/Services/GooglePlacesLookup.php` | Pencarian `place_id`, detail live, proxy foto |
| `app/Http/Controllers/Api/DirektoriImportController.php` | Endpoint impor admin |
| `app/Http/Controllers/Api/DirektoriGoogleController.php` | Endpoint live Google + proxy foto |
| `app/Console/Commands/PlacesSyncCommand.php` | `places:sync` |
| `app/Console/Commands/PlacesMatchCommand.php` | `places:match` |
| `database/migrations/2026_10_11_134944_tambah_kolom_openstreetmap_google_direktori.php` | Kolom `osm_id`, `google_place_id`, `sumber`, `jam_buka_teks` |
| `tests/Feature/OsmImporterTest.php`, `tests/Feature/DirektoriGoogleTest.php` | Tes fitur (Overpass & Google di-*fake*) |
