# Rencana Pengerjaan TANGSEL LIA

Daftar langkah eksekusi dari nol sampai rilis. Centang `[x]` tiap langkah selesai.
Referensi teknis (endpoint, entitas, keputusan) ada di `CATATAN-TEKNIK-BERITA.md` —
file ini murni rencana kerja.

Urutan: **logis, tanpa patokan tanggal**. Revisi mockup digabung pada tahap frontend.

---

## Posisi sekarang

- [x] Proposal direvisi (2 peran, cakupan 5 fitur, AI, Sustainability, pilar 8)
- [x] Catatan teknis direvisi (peran 2, lapisan AI Gemini, alur laporan final)
- [x] Mockup Next.js statis ada di `tangsel-app/` — **tanpa fetch API, route `/berita` belum ada**
- [x] Backend `tangsel-api/` — Laravel 13 + Sanctum, 9 entitas ter-migrasi, seeder jalan, auth + `role:admin` siap
- [x] Tahap 0 selesai (13 test hijau)
- [x] Tahap 1 selesai (direktori kuliner/olahraga/kesehatan, 33 test hijau)
- [x] Tahap 2 selesai (modul berita + agregasi RSS 105 berita, 58 test hijau)
- [x] Tahap 3 selesai (laporan warga, 66 test hijau)
- [x] Direktori tempat: impor OpenStreetMap + rating/foto live Google Places (backend, 74 test hijau)
- [x] Impor OSM nyata dijalankan: **1.031 tempat asli** masuk DB (kuliner 783, olahraga 33, kesehatan 215)
- [x] Tahap 4 selesai (revisi mockup + wiring semua halaman + auth httpOnly cookie + dashboard admin/warga)
- [ ] Tahap 5 (kualitas & rilis) — sedang jalan
- [x] Tahap 4 fondasi: `tangsel-app/.env.local` + `src/lib/api.ts` (helper `apiFetch`) + `src/lib/format.ts` (label/format ID)
- [x] Tahap 4 direktori: `/kuliner`, `/tempat/[slug]`, `/olahraga` + `/olahraga/[slug]`, `/kesehatan` + `/kesehatan/[slug]` → data API nyata
- [x] Tahap 4 berita: `/berita` + `/berita/[slug]` → data API nyata
- [x] Tahap 4 auth: BFF `/api/auth/*` + `/api/laporan` + `/api/admin/*`, cookie httpOnly `tangsel_token`, `/login`, guard dashboard
- [x] Backend: `GET /laporan/meta` (opsi form) + `GET /admin/laporan` (daftar moderasi) + perbaikan `?mine=1` lewat Bearer token
- [x] Env: MySQL distart manual tiap restart (`mysqld --defaults-file=...\my.ini`), CA SSL `cacert.pem` + GD sudah di-php.ini WinGet

---

## Peta cepat: fitur → entitas → endpoint → halaman

| Fitur | Entitas | Endpoint utama (§14) | Halaman mockup |
|---|---|---|---|
| Beranda | — (agregasi) | `GET /berita/terbaru`, `GET /laporan` | `/` |
| Berita | `berita`, `berita_sumber` | `GET /berita`, `/terbaru`, `/{slug}`, `/meta`, `POST /admin/berita/refresh` | **belum ada** → buat `/berita`, `/berita/[slug]` |
| Tempat (kafe/warkop) | `tempat` | `GET /kuliner`, `/{slug}`, `/meta` + CRUD admin | `/kuliner`, `/tempat/[slug]` |
| Fasilitas olahraga | `fasilitas_olahraga` | `GET /olahraga`, `/{slug}`, `/meta` + CRUD admin | `/olahraga` |
| Layanan kesehatan | `layanan_kesehatan` | `GET /kesehatan`, `/{slug}`, `/meta` + CRUD admin | `/kesehatan` |
| Laporan warga | `laporan`, `laporan_foto` | `GET/POST /laporan`, `/{kode}`, `/{kode}/verifikasi`, `/{kode}/status` | `/laporan`, `/laporan/buat` |
| Auth | `users` | `POST /auth/register`, `/login`, `/logout`, `GET /auth/me` | login + admin (belum ada) |

---

## Tahap 0 — Fondasi backend

Sekali jadi, dipakai semua tahap berikutnya.

- [x] `composer create-project` di `tangselia/tangsel-api` (Laravel, PHP 8 di Laragon)
- [x] `.env`: database MySQL Laragon (`utf8mb4`), `APP_URL`, Sanctum stateful
- [x] Pasang & konfigurasi CORS untuk origin mockup (`localhost:3000`)
- [x] Migration **9 entitas + `laporan_foto` sekaligus** (jangan per-fitur):
      `users`, `kategori`, `kecamatan`, `tempat`, `fasilitas_olahraga`,
      `layanan_kesehatan`, `berita`, `berita_sumber`, `laporan`
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
- [x] Tabel `berita_sumber` + seeder sumber RSS (Google News query kota + Bing News)
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

## Tahap 3 — Laporan warga

Butuh auth dari Tahap 0. Alur 5 langkah: **Temukan Masalah → Kirim Foto dan GPS →
Verifikasi Admin → Diproses Admin → Selesai**.

- [x] `POST /laporan`: multipart, maks 3 foto, `latitude`/`longitude`, generate `kode` unik
- [x] `GET /laporan`: feed **hanya status `diverifikasi` ke atas** (sumber section beranda)
- [x] `GET /laporan?mine=1`: laporan milik pengguna login (tab "Laporan Saya")
- [x] `GET /laporan/{kode}`: detail + timeline (pemilik atau admin saja)
- [x] `POST /laporan/{kode}/verifikasi`: admin → `diverifikasi`/`ditolak` + `catatan_admin`
- [x] `POST /laporan/{kode}/status`: admin → `diproses`/`selesai`
- [x] Guard: hanya pemilik bisa lihat laporan miliknya; hanya admin boleh transisi status
- [x] Feature test alur penuh: kirim → verifikasi → proses → selesai (termasuk 403 untuk warga)

**Selesai kalau:** alur 5 langkah jalan lewat API dari awal sampai akhir, feed publik
hanya menampilkan laporan terverifikasi, test hijau.

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
- [x] `/dashboard/admin`: dashboard admin — daftar laporan masuk (verifikasi/tolak, ubah status),
      pantau sumber berita agregasi, tombol refresh agregasi
- [x] `/dashboard/user`: profil warga + "Laporan Saya" + keluar

### 4c. Wiring per halaman (urut, sambil cek kontrak ke catatan §14)

- [x] `/kuliner` + `/tempat/[slug]` — **perbaiki param `slug`** yang tadi diabaikan
- [x] `/olahraga` (+detail)
- [x] `/kesehatan` — tambah input pencarian + daftar **Apotek**
- [x] `/berita` + `/berita/[slug]`
- [x] `/laporan` + `/laporan/buat` — feed + tab "Laporan Saya" + form 5 langkah (kategori, GPS, maks 3 foto)
- [x] `/` — kartu Kabar (`href="#"` → `/berita`), section Kabar dari
      `GET /berita/terbaru`, "Pantau Aduan Sekitar" dari `GET /laporan`
- [x] Navigasi bawah: perbaiki `href="#"` tersisa (grep bersih)
- [x] Route guard: `/dashboard/admin` hanya untuk admin (warga dialihkan ke `/dashboard/user`)

**Selesai kalau:** `grep -r "href=\"#\""` bersih, tidak ada data hardcoded di halaman,
setiap halaman menampilkan data dari API.

---

## Tahap 5 — Kualitas & rilis

- [x] Backend: `php artisan test` hijau seluruhnya (77/77, 340 assertion)
- [x] Frontend: `npm run lint` (0 error) + `npm run build` tanpa error
- [ ] E2E manual alur lengkap: register warga → kirim laporan → login admin → verifikasi →
      proses → selesai → cek feed beranda
- [ ] Deploy: frontend Vercel, backend hosting cloud (Railway/Render/VPS), `.env` produksi
- [ ] Penjadwalan `berita:fetch` aktif di produksi (cron/scheduler hosting)
- [ ] Dokumentasi: `php artisan route:list --path=api` → README endpoint, screenshot halaman
- [ ] Backup database terjadwal

**Selesai kalau:** aplikasi bisa diakses dari HP di luar localhost dan seluruh alur di atas jalan.

---

## Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Gemini API limit/gagal | Fallback otomatis ke ringkasan feed; dicatat di log; admin bisa edit ringkasan |
| Ringkuman AI tidak akurat | Tautan artikel asli selalu tampil; isi artikel tidak disimpan; evaluasi 10 berita |
| Halaman artikel menolak diambil | Cek `robots.txt` dulu → bila ditolak, pakai ringkasan feed |
| RSS sumber mati | `try/catch` per sumber + `Log::warning`, proses lain lanjut |
| Kontrak response meleset dari kebutuhan halaman | Saat wiring, cek 1 endpoint per halaman langsung ke UI — ketahuan cepat |
| Belum ada data | Seeder data contoh per fitur sudah dari Tahap 0 |
