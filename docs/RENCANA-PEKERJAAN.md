# Rencana Pengerjaan TANGSEL LIA

Daftar langkah eksekusi dari nol sampai rilis. Centang `[x]` tiap langkah selesai.
Referensi teknis (endpoint, entitas, keputusan) ada di `CATATAN-TEKNIK-BERITA.md`,
`CATATAN-TEKNIK-DIREKTORI-TEMPAT.md`, dan `CATATAN-TEKNIK-LOWONGAN.md` —
file ini murni rencana kerja.

Urutan: **logis, tanpa patokan tanggal**. Revisi mockup digabung pada tahap frontend.

> **Catatan revisi (Oktober 2026).** Modul **Laporan Warga dihapus** dan
> digantikan **Modul Lowongan Kerja dan Magang**. Tabel `laporan` dan
> `laporan_foto` di-drop, alur lima langkah (Temukan Masalah → Selesai) diganti
> alur kurasi dua tahap (Ambil dari Sumber → Curasi Administrator → Tayang/Ditolak).
> Strategi data lowongan bersifat hibrida: agregasi *feed* RSS dari job board
> dan media lokal, lalu disaring wilayah dan dikurasi administrator.

---

## Posisi sekarang

- [x] Proposal direvisi (2 peran, cakupan 5 fitur, AI, Sustainability, pilar 8)
- [x] Catatan teknis direvisi (peran 2, lapisan AI Gemini, alur lowongan)
- [x] Mockup Next.js statis ada di `tangsel-app/` — sudah jadi: fetch API nyata
- [x] Backend `tangsel-api/` — Laravel 13 + Sanctum, entitas ter-migrasi, seeder jalan, auth + `role:admin` siap
- [x] Tahap 0 selesai (13 test hijau)
- [x] Tahap 1 selesai (direktori kuliner/olahraga/kesehatan, 33 test hijau)
- [x] Tahap 2 selesai (modul berita + agregasi RSS, 58 test hijau)
- [x] Tahap 3 selesai (modul lowongan kerja dan magang, menggantikan laporan warga)
- [x] Direktori tempat: impor OpenStreetMap + rating/foto live Google Places (backend, 74 test hijau)
- [x] Impor OSM nyata dijalankan: **1.031 tempat asli** masuk DB (kuliner 783, olahraga 33, kesehatan 215)
- [x] Tahap 4 selesai (revisi mockup + wiring semua halaman + auth httpOnly cookie + dashboard admin/warga)
- [ ] Tahap 5 (kualitas & rilis) — sedang jalan
- [x] Tahap 4 fondasi: `tangsel-app/.env.local` + `src/lib/api.ts` (helper `apiFetch`) + `src/lib/format.ts` (label/format ID)
- [x] Tahap 4 direktori: `/kuliner`, `/tempat/[slug]`, `/olahraga` + `/olahraga/[slug]`, `/kesehatan` + `/kesehatan/[slug]` → data API nyata
- [x] Tahap 4 berita: `/berita` + `/berita/[slug]` → data API nyata
- [x] Tahap 4 lowongan: `/lowongan` + `/lowongan/[slug]` → data API nyata, bagian "Lowongan Terbaru" di beranda
- [x] Tahap 4 auth: BFF `/api/auth/*` + proxy `/api/admin/[...path]`, cookie httpOnly `tangsel_token`, `/login`, guard dashboard
- [x] Backend: `GET /lowongan/meta` (opsi filter) + `GET /admin/lowongan` (daftar moderasi) + `PUT /admin/lowongan/{id}` (transisi status)
- [x] Data nyata lowongan: **77 lowongan** terambil dari 9 sumber RSS, 0 sumber gagal → 67 tayang / 10 ditolak setelah kurasi
- [x] Env: MySQL distart manual tiap restart (`mysqld --defaults-file=...\my.ini`), CA SSL `cacert.pem` + GD sudah di-php.ini WinGet

---

## Peta cepat: fitur → entitas → endpoint → halaman

| Fitur | Entitas | Endpoint utama | Halaman |
|---|---|---|---|
| Beranda | — (agregasi) | `GET /berita/terbaru`, `GET /lowongan/terbaru` | `/` |
| Berita | `berita`, `sumber` | `GET /berita`, `/terbaru`, `/{slug}`, `/meta`, `POST /admin/berita/refresh` | `/berita`, `/berita/[slug]` |
| Lowongan Kerja & Magang | `lowongan`, `sumber` | `GET /lowongan`, `/terbaru`, `/{slug}`, `/meta`, `GET/PUT/DELETE /admin/lowongan` | `/lowongan`, `/lowongan/[slug]` |
| Tempat (kafe/warkop) | `tempat` | `GET /kuliner`, `/{slug}`, `/meta` + CRUD admin | `/kuliner`, `/tempat/[slug]` |
| Fasilitas olahraga | `fasilitas_olahraga` | `GET /olahraga`, `/{slug}`, `/meta` + CRUD admin | `/olahraga` |
| Layanan kesehatan | `layanan_kesehatan` | `GET /kesehatan`, `/{slug}`, `/meta` + CRUD admin | `/kesehatan` |
| Auth | `users` | `POST /auth/register`, `/login`, `/logout`, `GET /auth/me` | `/login`, `/dashboard/*` |

---

## Tahap 0 — Fondasi backend

Sekali jadi, dipakai semua tahap berikutnya.

- [x] `composer create-project` di `tangselia/tangsel-api` (Laravel, PHP 8 di Laragon)
- [x] `.env`: database MySQL Laragon (`utf8mb4`), `APP_URL`, Sanctum stateful
- [x] Pasang & konfigurasi CORS untuk origin mockup (`localhost:3000`)
- [x] Migration entitas sekaligus (jangan per-fitur):
      `users`, `kategori`, `kecamatan`, `tempat`, `fasilitas_olahraga`,
      `layanan_kesehatan`, `berita`, `sumber`
- [x] Seeder: 7 kecamatan, kategori bawaan, 1 akun admin, data contoh tiap fitur
- [x] Auth: `POST /auth/register`, `/login`, `/logout`, `GET /auth/me` (Sanctum)
- [x] Middleware `role:admin` + alias route grup `/admin/*`
- [x] Struktur: `app/Http/Controllers/Api`, `app/Services`, `routes/api.php`

**Selesai kalau:** semua tabel ada, seeder jalan ulang tanpa error, `GET /api/auth/me`
mengembalikan data admin yang baru login.

---

## Tahap 1 — Fitur direktori (paling cepat kelihatan)

Pola sama untuk ketiganya — kerjakan berurutan, yang paling sederhana dulu.
**Tanpa Booking** pada olahraga.

- [x] `tempat`: `GET /kuliner` (filter jenis, kecamatan, pencarian, cursor)
- [x] `tempat`: `GET /kuliner/{slug}`, `GET /kuliner/meta`
- [x] `tempat`: `POST/PUT/DELETE` (admin)
- [x] `fasilitas_olahraga`: list, `{slug}`, `meta`, CRUD admin
- [x] `layanan_kesehatan`: list, `{slug}`, `meta`, CRUD admin (+ kolom `apotek`)
- [x] Feature test tiap endpoint (200, 404, filter, admin-only 403)
- [x] Impor otomatis dari OpenStreetMap: `OsmImporter`, `POST /admin/{fitur}/import`, `places:sync`
- [x] Generate deskripsi otomatis dari tag OSM (entri lama tidak ditimpa → suntingan admin aman)
- [x] Rating/foto live Google Places (New): `GET /{fitur}/{slug}/google`, proxy `/{fitur}/{slug}/foto`, `places:match`

> Detail desain: `CATATAN-TEKNIK-DIREKTORI-TEMPAT.md`. Catatan: endpoint live Google
> tetap berfungsi tanpa `GOOGLE_MAPS_API_KEY` (`data: null`), hanya impor OSM yang jalan tanpa key.

**Selesai kalau:** 3 fitur bisa dihitung dari API (`/meta` mengembalikan jumlah),
CRUD admin terproteksi, `php artisan test` hijau.

---

## Tahap 2 — Modul Berita + Perangkuman AI

Tahap paling kompleks. Urutannya: editorial dulu → agregasi → AI → penjadwalan.

- [x] CRUD berita **editorial** (judul, slug, konten, kategori, kecamatan, upload gambar)
- [x] Tabel `sumber` + seeder sumber RSS (Google News query kota + Bing News)
- [x] `NewsFetcher`: ambil feed per sumber, parse item (judul, link, tanggal, gambar, `<source>`)
- [x] Dedup: unique `url_asli`, lewati yang sudah ada
- [x] `ArticleContentFetcher`: ambil isi artikel asli **setelah cek `robots.txt`** — teks tidak disimpan
- [x] `AISummarizer`: panggil Google Gemini API (key via `.env`, konfigurasi `config/ai.php`),
      prompt berbahasa Indonesia, simpan `ringkasan` + `diringkas_oleh = ai`
- [x] **Fallback**: kalau fetch isi gagal / API limit / error → pakai ringkasan bawaan feed
      (`diringkas_oleh = feed`), `Log::warning`
- [x] Kategorisasi otomatis (`Categorizer`) ke `kategori_id`/`kecamatan_id` + nilai bawaan
- [x] Command `php artisan berita:fetch` + jadwal `everyFiveMinutes`/per jam di `routes/console.php`
- [x] `POST /admin/berita/refresh` (pemicu manual dari admin)
- [x] Test: **mock respons Gemini** (jangan panggil API asli saat test), test fallback, test dedup
- [ ] Evaluasi manual: 10 berita contoh dibandingkan ringkasan vs artikel asli

**Selesai kalau:** `berita:fetch` menghasilkan ≥10 berita dengan ringkasan + sumber + tautan asli,
kata "Diringkas AI" bisa ditandai dari `diringkas_oleh`, test hijau tanpa koneksi internet.

---

## Tahap 3 — Modul Lowongan Kerja dan Magang

Menggantikan modul Laporan Warga. Alur dua tahap: **Ambil dari Sumber →
Curasi Administrator → Tayang / Ditolak**. Tidak ada kiriman dari warga;
data masuk otomatis dari *feed* RSS lalu disaring dan dikurasi.

### 3a. Skema & sumber

- [x] Tabel `lowongan` (mirror `berita`): `kode`, `slug`, `judul`, `judul_hash`,
      `posisi`, `perusahaan`, `jenis`, `deskripsi`, `kualifikasi`, `cara_apply`,
      `url_asli`, `sumber`, `sumber_url`, `status`, `relevan`, `catatan_admin`,
      `tayang_at`, `kedaluwarsa_at`, `published_at`, `fetched_at`
- [x] `berita_sumber` → `sumber` + kolom `tipe enum('berita','lowongan')`
- [x] `kategori.scope` enum → `('berita','lowongan')`; 4 kategori scope `laporan` dihapus
- [x] Migration `drop_laporan_tables` (tabel `laporan`, `laporan_foto`)
- [x] Seeder 9 sumber lowongan: 4 Bing News RSS + 5 Google News RSS (operator `site:`)

### 3b. Pengambilan & pemrosesan

- [x] `LowonganFetcher`: unduh feed → dedup → ambil isi → ekstrak → klasifikasikan
- [x] Dedup ganda: `url_asli` **dan** SHA-1 judul ternormalisasi (`judul_hash`)
- [x] `AIEkstraksiLowongan`: panggil Gemini JSON untuk `posisi`, `perusahaan`,
      `deskripsi`, `kualifikasi`, `cara_apply` — AI **hanya** memindahkan fakta
      yang tertulis di sumber; field kosong dibiarkan kosong, tidak ditebak
- [x] Fallback `parseDariJudul()` + `rapikanHasil()` untuk sumber umum
- [x] `LowonganClassifier`: jenis `magang` | `karir` | `part-time` dari peta kata
      kunci deterministik (`config/lowongan.php`) — **tidak pernah** dari AI
- [x] `LowonganWilayah`: penyaringan wilayah, penanda positif menang atas penanda kota lain
- [x] `relevan` boolean: job board selalu relevan; sumber umum perlu penanda lowongan di judul
- [x] Deskripsi = cuplikan feed (sumber kebenaran), isi artikel hanya bahan baca AI
- [x] Command `php artisan lowongan:fetch` + `php artisan lowongan:expire`

### 3c. Kurasi & API

- [x] Status `baru` → `tayang`/`ditolak`, plus `kedaluwarsa` (otomatis 30 hari)
- [x] `AdminLowonganController::TRANSISI` — transisi tak sah → **422**
- [x] `GET /admin/lowongan` (filter status/jenis/relevan/q, cursor)
- [x] `PUT /admin/lowongan/{id}` (koreksi field hasil ekstraksi + ubah status)
- [x] `DELETE /admin/lowongan/{id}` (hapus yang tidak relevan)
- [x] `GET /lowongan`, `/terbaru`, `/{slug}`, `/meta` (filter jenis, kategori, kecamatan, q)
- [x] Panel kurasi admin di frontend (`AdminLowonganPanel`)

### 3d. Pengujian

- [x] Feature test: alur `baru → tayang`, transisi tak sah 422, filter, guard admin
- [x] Unit test `LowonganWilayahTest`: penanda positif menang, kota lain ditolak
- [x] Test dedup `judul_hash` dan `url_asli`
- [x] Jalankan `lowongan:fetch` nyata → 77 lowongan, 0 sumber gagal
- [x] Kurasi manual 77 item → 67 tayang / 10 ditolak
- [ ] Isi `GEMINI_API_KEY` agar `posisi`/`kualifikasi`/`cara_apply` terisi otomatis

> Detail desain: `CATATAN-TEKNIK-LOWONGAN.md`.

**Selesai kalau:** lowongan dari sumber tayang di halaman publik dengan status transparan,
filter jenis/kategori/kecamatan berfungsi, transisi status_invalid ditolak 422, test hijau.

---

## Tahap 4 — Frontend: revisi mockup + wiring per halaman

### 4a. Revisi struktur (sekali jadi)

- [x] Hapus route `src/app/agenda/`
- [x] Hapus route mock lama `/tempat/coffee-shop` + `/tempat/olahraga` (digantikan `/kuliner` + `/tempat/[slug]` dan `/olahraga` + `/olahraga/[slug]`; keduanya sempat menutup route dinamis `/tempat/[slug]`)
- [x] Beranda: hapus kartu Agenda, section "Sedang Ramai di Tangsel", segment control 3 mode
- [x] `olahraga`: hapus tombol Booking (kartu cukup rating, lokasi, jarak)
- [x] Susun `lib/api.ts`: helper fetch + tipe
- [x] Auth BFF: cookie httpOnly `tangsel_token` + `src/lib/auth.ts` (`getToken`, `getCurrentUser`, `authedFetch`) — token Bearer tidak pernah ke browser
- [x] Buat halaman **login** (warga & admin) — `src/components/auth/AuthForm.tsx` + `/login`

### 4b. Halaman baru

- [x] `/berita` (list: filter kategori/tipe, badge **"Diringkas AI"**, atribusi + tautan sumber)
- [x] `/berita/[slug]` (detail agregat menautkan artikel asli; editorial tampilkan konten)
- [x] `/lowongan` (list: filter jenis, bidang pekerjaan, kecamatan; kartu lowongan + status)
- [x] `/lowongan/[slug]` (detail: posisi, perusahaan, deskripsi, kualifikasi, cara melamar, tautan sumber)
- [x] `/dashboard/admin`: dashboard admin — panel kurasi lowongan, pantau sumber berita agregasi, tombol refresh agregasi
- [x] `/dashboard/user`: profil warga + keluar

### 4c. Wiring per halaman (urut, sambil cek kontrak ke catatan teknis)

- [x] `/kuliner` + `/tempat/[slug]` — **perbaiki param `slug`** yang tadi diabaikan
- [x] `/olahraga` (+detail)
- [x] `/kesehatan` — tambah input pencarian + daftar **Apotek**
- [x] `/berita` + `/berita/[slug]`
- [x] `/lowongan` + `/lowongan/[slug]` — data API nyata
- [x] `/` — kartu Kabar (`href="#"` → `/berita`), section Kabar dari
      `GET /berita/terbaru`, section "Lowongan Terbaru" dari `GET /lowongan/terbaru`
- [x] Navigasi: item "Lowongan", CTA accent "Buat Laporan" dihapus, nav mobile "Lapor" → "Lowongan"
- [x] Footer: "Laporan Warga" → "Lowongan Kerja"
- [x] Navigasi bawah: perbaiki `href="#"` tersisa (grep bersih)
- [x] Route guard: `/dashboard/admin` hanya untuk admin (warga dialihkan ke `/dashboard/user`)

**Selesai kalau:** `grep -r "href=\"#\""` bersih, tidak ada data hardcoded di halaman,
setiap halaman menampilkan data dari API.

---

## Tahap 5 — Kualitas & rilis

- [x] Backend: `php artisan test` hijau seluruhnya (**152 test, 554 assertion**)
- [x] Frontend: `npm run lint` (0 error) + `npm run build` tanpa error
- [x] Gaya kode: `vendor/bin/pint --format agent` bersih
- [ ] E2E manual alur lengkap: `lowongan:fetch` → kurasi admin → tayang → cek beranda
- [ ] Deploy: frontend Vercel, backend hosting cloud (Railway/Render/VPS), `.env` produksi
- [ ] Penjadwalan `berita:fetch` + `lowongan:fetch` + `lowongan:expire` aktif di produksi (cron/scheduler hosting)
- [ ] Dokumentasi: `php artisan route:list --path=api` → README endpoint, screenshot halaman
- [ ] Backup database terjadwal

**Selesai kalau:** aplikasi bisa diakses dari HP di luar localhost dan seluruh alur di atas jalan.

---

## Dokumen laporan

| Berkas | Isi |
|---|---|
| `docs/BAB 1 - TANGSEL LIA.md` | sumber teks BAB 1 (format markdown) |
| `docs/BAB 1 - TANGSEL LIA.docx` | hasil render BAB 1 (Times New Roman 12, spasi 1.5) |
| `docs/bab1_content.py` | isi teks BAB 1 dalam satu sumber data |
| `docs/gen_bab1.py` | generator yang menulis `.md` **dan** `.docx` dari sumber tersebut |
| `docs/CATATAN-TEKNIK-LOWONGAN.md` | catatan teknis modul Lowongan Kerja & Magang |
| `docs/CATATAN-TEKNIK-BERITA.md` | catatan teknis modul Berita |
| `docs/CATATAN-TEKNIK-DIREKTORI-TEMPAT.md` | catatan teknis modul direktori tempat |

Regenerasi BAB 1 setelah teks diubah:

```bash
pip install python-docx
python docs/gen_bab1.py
```

Perintah itu menulis ulang `BAB 1 - TANGSEL LIA.md` dan
`BAB 1 - TANGSEL LIA.docx` sekaligus, sehingga keduanya selalu identik.

---

## Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Gemini API limit/gagal | Fallback otomatis ke ringkasan feed; dicatat di log; admin bisa edit ringkasan |
| Ringkasan AI tidak akurat | Tautan artikel asli selalu tampil; isi artikel tidak disimpan; evaluasi 10 berita |
| Halaman artikel menolak diambil | Cek `robots.txt` dulu → bila ditolak, pakai ringkasan feed |
| RSS sumber mati | `try/catch` per sumber + `Log::warning`, proses lain lanjut |
| Tautan Google News tidak bisa di-resolve | Tautan `news.google.com` disimpan apa adanya sebagai `url_asli`; sumber Bing News memakai tautan langsung |
| Lowongan dari luar wilayah ikut masuk | `LowonganWilayah::layak()` menyaring penanda kota lain; penanda wilayah positif menang |
| Tebakan AI membuat field fiktif | Prompt melarang mengarang; field kosong dibiarkan kosong; parser cadangan berbasis pola judul |
| Lowongan basi tetap tampil | Status `tayang` diberi `kedaluwarsa_at` 30 hari, dijalankan `lowongan:expire` |
| Otorisasi bocor ke browser | Token hanya di cookie httpOnly; admin lewat proxy Route Handler Next.js |
| Kontrak response meleset dari kebutuhan halaman | Saat wiring, cek 1 endpoint per halaman langsung ke UI — ketahuan cepat |
| Belum ada data | Seeder data contoh per fitur sudah dari Tahap 0 |
