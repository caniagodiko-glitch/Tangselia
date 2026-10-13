# Catatan Teknis & Fitur — TANGSEL LIA

> Peta fitur platform TANGSEL LIA, beserta catatan teknis pengumpulan berita secara otomatis
> dari berbagai sumber.
>
> **Status:** dokumen desain, belum diimplementasikan.
> **Tanggal pengujian data sumber berita:** 5 Oktober 2026 — angka jumlah item per feed berubah setiap hari.
> **Proyek:** Tugas Akhir — Diko Daen Afinto (1152200036), Institut Teknologi Indonesia.

## Daftar Isi

**Bagian I — Catatan Teknis Modul Berita**

1. Latar Belakang Kebutuhan
2. Terminologi: "Scraping" Itu Tiga Hal Berbeda
3. Hasil Uji Nyata terhadap Sumber Berita
4. Rekomendasi Arsitektur Pengumpulan
5. Dasar Justifikasi (untuk Bab 3)
6. Rencana Implementasi
7. Risiko dan Batasan
8. Batasan Cakupan (Belum Dikerjakan)

**Bagian II — Peta Fitur**

9. Ringkasan Cakupan
10. Peta Fitur dan Status
11. Detail Per Fitur
12. Matriks Peran Pengguna
13. Entitas Data dan Relasi
14. Kebutuhan Endpoint API
15. Timeline Implementasi
16. Gap dan Open Questions

---

## 1. Latar Belakang Kebutuhan

Proposal memposisikan modul Berita sebagai **"warta pemerintahan dan informasi publik"**, yaitu
berita yang dikelola oleh **administrator**. Namun kebutuhan warga terhadap berita adalah berita
yang **berubah sendiri setiap hari** dan tersebar di banyak publisher, sehingga mustahil dimasukkan
satu per satu oleh administrator secara manual.

Untuk menjawab kebutuhan tersebut, modul Berita dirancang dengan dua sumber data yang berbeda
namun berada dalam satu tabel:

| Tipe | Sumber | Siapa yang mengisi | Perlu auto-update? |
|---|---|---|---|
| `editorial` | Redaksi | Ditulis administrator | Tidak |
| `agregat` | RSS + agregator berita | Sistem otomatis | **Ya, tiap jam** |

Pengambilan data `agregat` inilah yang menjadi bahasan teknis utama dokumen ini.

---

## 2. Terminologi: "Scraping" Itu Tiga Hal Berbeda

Istilah "scraping" dipakai secara luas, tetapi dalam praktik ada tiga spesies teknik yang berbeda
karakteristik, biaya, dan risikonya:

| Istilah | Cara kerja | Alat |
|---|---|---|
| **HTML/Web Scraping** | Mengunduh halaman HTML sebuah situs, lalu mem-*parse* struktur DOM untuk mengekstrak judul dan artikel | `DOMDocument` + XPath, Cheerio, Selenium |
| **Feed Parsing (RSS/Atom)** | Publisher **menyediakan sendiri** berkas XML berisi data berita terstruktur. Sistem hanya membaca, **tidak perlu menyentuh HTML sama sekali** | `simplexml_load_string`, `fast-xml-parser` |
| **API Aggregation** | Memanggil API pihak ketiga yang sudah menghimpun artikel dari ribuan publisher | NewsAPI, GNews, GDELT, Google News RSS |

**Implikasi untuk proyek ini:** yang dimaksud dosen secara substansi adalah *"berita terkumpul
otomatis dari banyak sumber, bukan input manual"*. Feed parsing **memenuhi kebutuhan tersebut
secara penuh**, dan justru lebih kokoh serta lebih patuh aturan daripada HTML scraping.

---

## 3. Hasil Uji Nyata terhadap Sumber Berita

Pengujian dilakukan langsung terhadap server, tanpa perantara, untuk memverifikasi sumber mana yang
memang dapat dipakai.

### 3.1 Sumber yang berhasil dipakai

| Sumber | Jenis | Item | Thumbnail | Catatan |
|---|---|---|---|---|
| Google News RSS — `"Tangerang Selatan" when:7d` | Agregator | **100** | — | Elemen `<source>` memuat nama dan URL publisher |
| Google News RSS — `"Tangerang Selatan" when:30d` | Agregator | 100 | — | Cadangan bila berita 7 hari kurang |
| Bing News RSS — `"Tangerang Selatan"` | Agregator | 12 | — | **URL artikel asli dapat diekstrak** dari query parameter `url=` |
| `banten.antaranews.com/rss/terkini.xml` | Feed langsung | 20 | Ya (`<media:thumbnail>`) | ANTARA News Banten, kanal resmi |
| `www.tangerangnews.com/rss` | Feed langsung | 15 | — | Media lokal Tangerang/Tangsel |
| `www.bantennews.co.id/feed` | Feed langsung | 10 | Ya | — |
| `posbanten.com/feed` | Feed langsung | 10 | — | Situsnya berbasis WordPress |
| `www.sinarbanten.com/feed/` | Feed langsung | 10 | — | Situsnya berbasis WordPress |
| `www.antaranews.com/rss/terkini.xml` | Feed langsung | 50 | — | Kanal nasional, untuk konteks |

**Publisher yang berhasil teridentifikasi lewat Google News RSS:** `berita.tangerangselatankota.go.id`
(situs resmi pemerintah Kota Tangerang Selatan), ANTARA News Banten, Surya Nenggala, respublika.id,
detikNews, dan RM.ID.

### 3.2 Sumber tambahan berbasis REST API

`posbanten.com` dan `www.sinarbanten.com` berjalan di atas **WordPress**, sehingga menyediakan REST API
bawaan yang jauh lebih rapi daripada scraping HTML:

```
GET https://posbanten.com/wp-json/wp/v2/posts?search=Tangerang%20Selatan&_embed=1
    -> 200, X-WP-Total: 67

GET https://www.sinarbanten.com/wp-json/wp/v2/posts?search=Tangerang%20Selatan&_embed=1
    -> 200, X-WP-Total: 97
```

Catatan: hasil pencarian bersifat historis (berita tertua Januari–April 2026), sehingga **hanya dipakai
sebagai pelengkap**, bukan sumber berita terkini.

### 3.3 Sumber yang gagal dipakai, dan alasannya

| Sumber | Metode | Hasil Uji | Alasan |
|---|---|---|---|
| `berita.tangerangselatankota.go.id` | HTML scraping | **403 Forbidden** | Diblokir firewall aplikasi (WAF), termasuk pada permintaan `robots.txt` |
| `rss.detik.com` | RSS | Koneksi gagal | Menolak koneksi non-browser dari lingkungan pengembangan |
| `www.kompas.com/rss` | RSS | **404 Not Found** | Path feed tidak tersedia lagi |
| `rss.tempo.co` | RSS | 403, tidak konsisten | Perilaku berbeda antar-request |
| `api.gdeltproject.org` (GDELT) | API Aggregation | **429 Too Many Requests** (dua percobaan) | Rate limit agresif terhadap IP lokal |
| NewsAPI / GNews / Mediastack | API Aggregation | Belum diuji | Memerlukan pendaftaran dan API key; free tier terbatas |

### 3.4 Target HTML scraping yang diizinkan

Apabila demonstrasi scraping HTML tetap diperlukan, target berikut sudah diverifikasi **aman** karena
`robots.txt`-nya tidak melarang area publik:

| Situs | Isi `robots.txt` | HTTP | Catatan |
|---|---|---|---|
| `www.tangerangnews.com` | `User-agent: * Disallow: /admin/` | 200 | Kandidat terbaik; sudah punya RSS, sehingga scraping bersifat cadangan |
| `posbanten.com` | `Disallow: /wp-admin/` plus sitemap | 200 | Lebih baik memakai `/wp-json/wp/v2/posts` |
| `www.sinarbanten.com` | `Disallow: /wp-admin/` plus sitemap | 200 | Sama, memakai WordPress REST API |

---

## 4. Rekomendasi Arsitektur Pengumpulan

Tiga lapis, dengan feed parsing sebagai tulang punggung.

| Lapis | Isi | Kontribusi | Alasan |
|---|---|---|---|
| **1 — Feed langsung** | Enam RSS Banten/Tangsel | sekitar 70% | Stabil; publisher sendiri yang menyediakan; sebagian punya thumbnail |
| **2 — Agregator** | Google News RSS dan Bing News RSS | sekitar 30% | Cakupan luas, banyak publisher, ada atribusi sumber; Bing menyediakan URL asli |
| **3 — HTML scraping** | `tangerangnews.com` (opsional) | sekitar 5% | Hanya untuk membuktikan penerapan teknik scraping HTML secara harfiah |

**Keputusan akhir:** lapis 3 (HTML scraping) **tidak dipakai** — pengumpulan cukup dari lapis 1
dan 2. Pengambilan halaman artikel hanya dilakukan oleh `ArticleContentFetcher` sebagai bahan
ringkuman AI (§4.2), bukan untuk mempublikasikan isi artikel.

### 4.1 Dua temuan teknis yang menentukan desain

**a. Ekstraksi URL asli dari Bing News.** Setiap elemen `<link>` pada Bing News RSS berbentuk redirect:

```
http://www.bing.com/news/apiclick.aspx?ref=FexRss&aid=&tid=...&url=https%3a%2f%2fwww.rm.id%2fbaca-berita%2f...&c=...&mkt=en-id
```

URL artikel asli tersembunyi pada parameter `url=` dalam bentuk URL-encoded. Setelah di-decode, URL
tersebut dapat dipakai langsung, termasuk untuk mengambil `og:image` sebagai thumbnail.

**b. Keterbatasan link Google News.** Link pada Google News RSS berupa URL redirect
`news.google.com/rss/articles/...` yang **tidak dapat diteruskan ke artikel asli di sisi server**.
Hasil pengujian: respons HTTP 200 tanpa redirect, dan hanya berjalan apabila dibuka di peramban.
Konsekuensinya:

- Atribusi sumber tetap aman diambil dari elemen `<source>` (nama dan URL publisher).
- Untuk URL artikel asli, andalkan **Bing News** sebagai sumber utama.

### 4.2 Lapis Pemrosesan: Perangkuman AI (Google Gemini)

Setelah terkumpul dari lapis 1 dan 2, artikel melewati lapis pemrosesan yang menghasilkan konten
portal:

1. `ArticleContentFetcher` mengambil halaman artikel asli sebagai bahan ringkuman — setelah
   memeriksa `robots.txt`. Teks hanya dipakai sementara, tidak disimpan.
2. `AISummarizer` mengirimkan judul dan isi artikel ke **Google Gemini API** (free tier) dengan
   prompt berbahasa Indonesia untuk menghasilkan satu ringkasan orisinal per berita.
3. Ringkuman disimpan ke kolom `ringkasan` dengan penanda `diringkas_oleh = ai`, beserta sumber
   dan tautan asli; tampil dengan badge **"Diringkas AI"** di antarmuka.
4. Bila halaman artikel tidak dapat diambil atau API gagal/limit, sistem **fallback** memakai
   ringkasan bawaan feed RSS.

Konfigurasi model, API key (melalui environment variable), dan prompt disimpan pada `config/ai.php`.

---

## 5. Dasar Justifikasi (untuk Bab 3)

1. **Stabilitas.** RSS/XML memiliki kontrak terstruktur yang disepakati publisher. HTML scraping
   langsung rusak begitu situs melakukan perubahan tampilan. Bukti empiris pada pengujian ini:
   enam feed hidup konsisten, sementara `detik` menolak koneksi dan situs resmi memblokir dengan 403.
2. **Legalitas dan etika.** `robots.txt` selalu diperiksa sebelum melakukan scraping. Situs yang
   memblokir akses otomatis (403) tidak dipaksa diakses. Sistem **hanya menyimpan judul, ringkasan,
   dan tautan**, bukan isi artikel penuh, dan **atribusi sumber selalu ditampilkan** di antarmuka.
   Praktik ini merupakan pola lazim agregator berita.
3. **Biaya pemeliharaan.** Daftar sumber dan peta keyword disimpan deklaratif pada
   `config/berita.php`. Menambah sumber baru cukup menambahkan satu baris konfigurasi, tanpa
   menulis parser HTML baru untuk setiap situs.
4. **Cakupan.** Penggabungan aggregator dengan feed lokal menghasilkan berita dari lebih dari delapan
   publisher, jauh lebih luas daripada satu situs resmi tunggal.

### 5.1 Contoh penjelasan untuk dosen

> "Bapak, kami menggunakan tiga teknik pengumpulan data. Yang utama adalah pembacaan **RSS/Atom**,
> yaitu format yang memang disediakan secara resmi oleh penerbit berita, sehingga legal dan stabil.
> Kami melengkapinya dengan **agregator berita** (Google News dan Bing News) agar cakupannya luas dan
> berasal dari banyak penerbit. Untuk sumber yang tidak menyediakan feed, kami menggunakan
> **scraping HTML**, tetapi hanya setelah memeriksa `robots.txt`. Yang disimpan hanyalah judul,
> ringkasan, dan tautan ke sumber aslinya, dengan atribusi yang jelas, bukan menyalin isi artikel.
> Kami memilih pendekatan ini karena situs resmi pemerintah memblokir akses otomatis dengan kode 403,
> sehingga scraping ke sana tidak akan berhasil."

---

## 6. Rencana Implementasi

### 6.1 Struktur Folder

```
tangselia/
  docs/                       proposal, mockup, catatan teknis
  tangsel-app/                Next.js 16 - presentation layer
  tangsel-api/                Laravel 13 - REST API + MySQL
```

> **Catatan arsitektur:** `tangsel-api` adalah aplikasi **independen**. Database, konfigurasi, dan
> kredensial terpisah. Tidak ada pembacaan maupun penulisan data ke project lain.

### 6.2 Prasyarat Lingkungan

| Masalah | Tindakan perbaikan |
|---|---|
| `composer.phar` bawaan Laragon rusak (`PharException: manifest cannot be larger than 100 MB in phar`) | Unduh ulang `composer.phar` dari `getcomposer.org` |
| Ekstensi `curl` nonaktif di `php.ini` (`;extension=curl`) | Aktifkan baris tersebut; `php_curl.dll` sudah ada. Diperlukan oleh HTTP client Laravel |
| MySQL dan Apache belum berjalan | Jalankan **Start** pada Laragon, lalu buat database `tangsel_api` |

PHP 8.4.22 memenuhi syarat Laravel 13 (`^8.3`). Ekstensi `simplexml`, `dom`, dan `pdo_mysql` sudah
aktif.

### 6.3 Basis Data

| Tabel | Kolom Inti | Catatan |
|---|---|---|
| `kategori` | `nama`, `slug`, `scope`, `ikon`, `urutan` | Tabel bersama untuk 8 kategori informasi; dipakai modul Berita dulu, modul lain memakai ulang tanpa perubahan desain |
| `kecamatan` | `nama`, `slug`, `urutan` | Tujuh kecamatan: Ciputat, Ciputat Timur, Pamulang, Pondok Aren, Serpong, Serpong Utara, Setu |
| `berita` | `tipe` (enum `editorial`/`agregat`), `judul`, `slug`, `ringkasan`, `diringkas_oleh` (enum `feed`/`ai`/`editor`), `konten`, `url_asli`, `sumber`, `sumber_url`, `gambar`, `kategori_id`, `kecamatan_id`, `editor_id`, `status`, `published_at`, `fetched_at` | Satu tabel. `konten` dan `editor_id` hanya untuk `editorial`; `url_asli` hanya untuk `agregat`. Index pada `published_at`, `kategori_id`, `kecamatan_id`, serta unique pada `url_asli` untuk mencegah duplikasi |
| `berita_sumber` | `nama`, `jenis`, `feed_url`, `aktif`, `last_fetched_at`, `last_status`, `last_error`, `item_count` | Tabel pendukung agregasi untuk pemantauan kesehatan sumber |

### 6.4 Konfigurasi Sumber

Seluruh sumber dideklarasikan pada `config/berita.php`:

- **Google News:** `"Tangerang Selatan" when:7d`, `"Tangerang Selatan" when:30d`,
  `Tangerang Selatan UMKM when:14d`, `Pamulang OR Ciputat OR Setu`, `Serpong OR BSD`
- **Bing News:** `"Tangerang Selatan"`, `Tangerang Selatan kesehatan`, `Tangerang Selatan olahraga`
- **Feed langsung:** enam feed pada tabel 3.1
- **Peta kategori:** `Tata Kota & Lingkungan`, `Ekonomi & UMKM`, `Transportasi`, `Pendidikan`,
  `Kesehatan`, `Olahraga`, `Hiburan`
- **Peta kecamatan:** tujuh kecamatan Kota Tangerang Selatan

### 6.5 Lapisan Service (Backend)

| Service | Tanggung jawab |
|---|---|
| `FeedParser` | Membaca RSS 2.0, Atom, dan RDF melalui `simplexml_load_string`, tanpa paket tambahan |
| `BingLinkResolver` | Ekstraksi URL artikel asli dari query parameter `url=` pada Bing News |
| `GoogleNewsResolver` | Pengambilan atribusi dari elemen `<source>`, serta pengganti link redirect |
| `ImageResolver` | Pengambilan `og:image` dari HTML artikel, khusus URL yang sudah ter-resolve, dibatasi sepuluh item per proses |
| `Categorizer` | Pemetaan kata kunci ke `kategori_id` dan `kecamatan_id`, dengan nilai bawaan |
| `ArticleContentFetcher` | Pengambilan isi artikel asli sebagai bahan ringkuman, setelah pemeriksaan `robots.txt`; teks tidak disimpan permanen |
| `AISummarizer` | Perangkuman via Google Gemini API dalam alur `berita:fetch`: menyusun prompt, memanggil API, menyimpan ringkuman dengan `diringkas_oleh = ai`, dan fallback ke ringkasan feed bila gagal |

Konfigurasi model, API key (environment variable), dan prompt AI berada pada `config/ai.php`.

Setiap sumber diproses dengan `try/catch` terpisah dan pencatatan `Log::warning`, sehingga satu sumber
gagal tidak menghentikan proses keseluruhan.

### 6.6 Penjadwalan

```bash
php artisan berita:fetch                 # seluruh sumber
php artisan berita:fetch --source=bing   # sumber tertentu
php artisan seed:berita                  # kategori, kecamatan, dan contoh berita editorial
```

Penjadwalan tiap jam didaftarkan pada `routes/console.php`:

```php
Schedule::command('berita:fetch')->hourly();
```

Untuk pengembangan lokal, jalankan `php artisan schedule:work`. Endpoint pemicu manual
(`POST /api/admin/berita/refresh`) disiapkan agar penjadwalan produksi dapat ditambahkan kemudian.

### 6.7 Endpoint API

```
GET  /api/berita?kategori=&kecamatan=&sumber=&q=&tipe=&cursor=&per_page=12
GET  /api/berita/terbaru?limit=5
GET  /api/berita/{slug}
GET  /api/berita/meta
POST /api/admin/berita/refresh      (stub, dilindungi token)
GET  /api/admin/berita/sumber       (stub, pemantauan sumber)
```

### 6.8 Lapisan Frontend

| Berkas | Isi |
|---|---|
| `src/lib/api.ts` | URL dasar API dari environment variable sisi server, menghindari CORS dan menjaga token tetap aman |
| `src/lib/berita.ts` | Klien bertipe dengan `fetch(..., { next: { revalidate: 3600 } })` untuk pembaruan otomatis tiap jam |
| `src/app/berita/page.tsx` | Daftar berita, filter kategori dan kecamatan, pencarian, "Muat lagi" (cursor), serta skeleton |
| `src/app/berita/[slug]/page.tsx` | Detail: gambar, ringkasan, badge sumber dan tipe, tanggal, tombol "Baca di sumber asli" (eksternal), `generateMetadata`. **`params` merupakan `Promise` pada Next.js 16** |
| `src/app/page.tsx` | Section "Kabar Kota Tangsel" diisi dari data nyata; navigasi bawah "Kabar" diarahkan ke `/berita` |
| `src/app/api/refresh/route.ts` | `revalidateTag('berita', 'max')` agar tampilan diperbarui segera setelah proses pengambilan data |

Konsistensi visual dijaga mengikuti purwarupa yang sudah ada: `bg-gray-50`, `rounded-2xl`,
`border-gray-100`, aksen `orange-500`, serta ikon `lucide-react`. Gambar memakai elemen `<img>` biasa
dengan cadangan gradien per kategori, karena host gambar berita bervariasi sehingga konfigurasi
`images.remotePatterns` pada `next.config` tidak diperlukan.

### 6.9 Pengujian

| Lingkup | Metode |
|---|---|
| Parser dan resolver | Feature test memakai berkas fixture XML lokal, **tanpa akses jaringan** |
| Integritas data | Pengujian duplikasi: jalankan pengambilan dua kali, pastikan jumlah baris tidak bertambah |
| Endpoint API | Feature test untuk filter, pagination, dan respons 404 |
| Ringkuman AI | Feature test dengan respons Gemini **mock** (tanpa memanggil API saat pengujian), pengecekan fallback saat API gagal, dan evaluasi kualitas ringkuman pada 10 berita contoh |
| Kualitas kode | `php artisan test`, `npm run lint`, `npm run build` |

---

## 7. Risiko dan Batasan

| Risiko | Mitigasi |
|---|---|
| Link Google News tidak dapat diteruskan ke artikel asli di sisi server | Atribusi diambil dari elemen `<source>`; URL asli diandalkan pada Bing News; tautan tetap membuka artikel di peramban |
| Situs resmi pemerintah memblokir akses otomatis (403) | Situs resmi tetap terwakili melalui Google News RSS sebagai sumber sekunder; tidak dilakukan upaya melewati blokir |
| Jumlah berita per hari dari feed langsung relatif sedikit | Dilengkapi aggregator Google News dan Bing News |
| Perubahan struktur XML feed publisher | Parser membaca RSS 2.0, Atom, dan RDF sekaligus; kegagalan satu sumber dicatat dan tidak menghentikan proses |
| Gambar berita tidak selalu tersedia | Penyimpanan URL gambar eksternal dengan cadangan visual per kategori |
| Gemini API gagal atau kena limit | Fallback otomatis memakai ringkasan bawaan feed; kegagalan dicatat pada log |
| Ringkuman AI tidak akurat (hallucination) | Tautan artikel asli selalu ditampilkan, admin dapat mengubah ringkuman, dan isi artikel penuh tidak disimpan |
| Halaman artikel menolak diambil | `ArticleContentFetcher` memeriksa `robots.txt` lebih dulu; bila ditolak, dipakai ringkasan feed |

---

## 8. Batasan Cakupan (Belum Dikerjakan)

- Autentikasi Sanctum, pembeda peran, dan dashboard administrator; endpoint-nya telah disiapkan
  sebagai stub agar tidak bentrok dengan jadwal tahap backend.
- Modul lain: Jelajah dan Laporan Warga. (Event/Festival tidak lagi masuk cakupan.)
- Penjadwalan produksi; endpoint pemicu sudah siap, tinggal dihubungkan ke cron.
- Penyimpanan isi artikel penuh; yang disimpan hanya ringkasan, tautan, dan atribusi sumber.
- Uji beban, uji penetrasi, dan kuisioner pengguna, karena di luar lingkup penelitian.

---

# BAGIAN II — PETA FITUR

## 9. Ringkasan Cakupan

Tahap ini mencakup lima fitur: **Berita**, **Tempat (kafe & warkop)**, **Fasilitas Olahraga**,
**Layanan Kesehatan**, dan **Laporan Warga**. Tiga modul dari proposal — Event/Festival, UMKM Lokal,
dan Komunitas — ditunda dan tidak termasuk tahap ini.

Keputusan cakupan yang sudah dikunci:

| Topik | Keputusan |
|---|---|
| Laporan fasilitas | Dimaknai sebagai **laporan warga / aduan kerusakan**, dengan alur lima langkah |
| Cakupan | Lima fitur tersebut saja; Event/Festival, UMKM Lokal, dan Komunitas tidak masuk tahap ini |
| Kafe dan warkop | **Satu entitas** dengan pembeda kolom `jenis` |
| Sumber berita | RSS dan agregator berita saja (lihat Bagian I); scraping HTML untuk pengumpulan tidak dipakai |
| Perangkuman berita | **AI Google Gemini**: artikel dari RSS/agregator diringkas sebelum tampil, dengan atribusi sumber dan tautan asli |
| Peran pengguna | **Dua peran: administrator dan warga.** Editor konten dan instansi dihapus — admin mengelola konten serta meninjau laporan |
| Laporan di beranda | Laporan tampil pada feed publik dan beranda **setelah diverifikasi administrator** |
| Beranda | Kartu Agenda, section trending event, dan segment control tiga mode dihapus dari mockup |
| Booking olahraga | Tidak dibuat; tombol Booking dihilangkan |
| Sustainability | Analisis keberlanjutan platform ditulis sebagai subsection proposal |
| Backend | Aplikasi Laravel baru di `tangselia/tangsel-api`, database terpisah |

---

## 10. Peta Fitur dan Status

Legenda status: **Sudah** = mockup ada dan cukup utuh · **Perlu** = mockup ada tapi belum menyentuh
database · **Belum** = belum ada sama sekali.

| Fitur | Berkas | Status | Keterangan |
|---|---|---|---|
| Berita | `src/app/berita/page.tsx`, `src/app/berita/[slug]/page.tsx` | **Sudah** | List + detail terhubung API; badge "Diringkas AI" + tautan sumber, editorial tampil `konten` |
| Tempat (kafe, warkop) | `src/app/kuliner/page.tsx`, `src/app/tempat/[slug]/page.tsx` | **Sudah** | Detail membaca `slug`; komponen bersama `DetailDirektori`/`DirektoriListView` |
| Fasilitas olahraga | `src/app/olahraga/page.tsx`, `src/app/olahraga/[slug]/page.tsx` | **Sudah** | Tanpa Booking; kartu + detail dari API |
| Layanan kesehatan | `src/app/kesehatan/page.tsx`, `src/app/kesehatan/[slug]/page.tsx` | **Sudah** | Input pencarian + filter; daftar Apotek ikut |
| Laporan warga | `src/app/laporan/page.tsx`, `src/app/laporan/buat/page.tsx` | **Sudah** | Feed + tab "Laporan Saya"; form 5 langkah (kategori, GPS, maks 3 foto) → BFF `/api/laporan` |
| Auth & dashboard | `src/app/login/page.tsx`, `src/app/dashboard/user/page.tsx`, `src/app/dashboard/admin/page.tsx` | **Sudah** | Login/daftar; dashboard warga + admin (verifikasi/tolak, ubah status, refresh agregasi) |

### 10.1 Kondisi Beranda

> **Diperbarui 12 Okt 2026 — sudah beres.** Beranda menarik data nyata: kartu **Kabar**
> mengarah ke `/berita`, section "Kabar Kota" dari `GET /berita/terbaru`, "Pantau Aduan
> Sekitar" dari `GET /laporan`; kartu Agenda + trending dihapus dan `href="#"` bersih.
> Catatan lama di bawah disimpan sebagai jejak perencanaan.

`src/app/page.tsx` sudah memiliki enam kartu layanan pada baris 105 sampai 110. Dua di antaranya
belum punya tujuan: **Kabar** memakai `href="#"` dan **Aduan** sudah benar mengarah ke `/laporan`.
Navigasi bawah pada baris 353 sampai 357 juga masih menyisakan `href="#"` untuk **Kabar**.

Section "Kabar Kota Tangsel" pada baris 239 sampai 285 berisi tiga berita hardcoded; section ini
nantinya diisi dari data nyata modul Berita.

**Keputusan revisi beranda:** kartu **Agenda**, section trending event ("Sedang Ramai di Tangsel"),
dan segment control tiga mode (Warga/UMKM/Aparatur) **dihapus** — beranda hanya melayani lima
fitur dalam cakupan, dan section "Pantau Aduan Sekitar" diisi laporan yang sudah diverifikasi admin.

---

## 11. Detail Per Fitur

### 11.1 Berita

Rincian lengkap ada di **Bagian I** dokumen ini. Yang relevan untuk pemetaan fitur:

- Satu tabel `berita` dengan pembeda `tipe` enum `editorial` (ditulis administrator) dan `agregat`
  (diambil otomatis dari sumber berita).
- Modul ini satu-satunya yang membutuhkan penjadwalan otomatis, yaitu `berita:fetch` tiap jam.
- Hasil agregat diringkas oleh **AI (Google Gemini)** sebelum tampil (§4.2), ditandai kolom
  `diringkas_oleh`; admin dapat mengubah ringkuman bila perlu.

### 11.2 Tempat — Kafe & Warkop

**Data pada mockup.** Halaman `/kuliner` memuat search dengan placeholder "Cari cafe, resto...",
lima chip kategori (Semua, Coffee Shop, Restoran Keluarga, Street Food, Dessert), dan grid dua kolom
dengan empat item contoh: Kala di Kala Space & Coffee (Pamulang, 4.8), Ruang Seduh Bambu Kuning
(Ciputat, 4.7), Sore di Serpong (Serpong, 4.9), dan Kopitagram (Bintaro, 4.6). Setiap kartu memuat
nama, lokasi, rating, dan tautan ke `/tempat/[slug]`.

Halaman detail memuat badge kategori, rating, nama, deskripsi, alamat, jam operasional, daftar
fasilitas (WiFi Cepat, Stop Kontak, Area Smoking, Mushola, Parkir Luas), serta tombol "Lihat Menu"
dan "Arahkan".

**Kolom yang dibutuhkan.**

| Kolom | Keterangan |
|---|---|
| `jenis` | enum: `kafe`, `warkop`, `restoran` — membedakan kopi dan warung |
| `nama`, `slug`, `deskripsi` | Identitas dan ringkasan |
| `alamat`, `latitude`, `longitude` | Lokasi, untuk fitur "Arahkan" |
| `kecamatan_id` | Foreign key ke tabel `kecamatan` |
| `jam_buka`, `jam_tutup` | Jam operasional |
| `rentang_harga` | Estimasi harga, untuk pencarian keterjangkauan harga |
| `rating`, `rating_kount` | Rating dan jumlah penilai |
| `fasilitas` | JSON: WiFi, stop kontak, area smoking, mushola, parkiran |
| `menu` | JSON atau tabel anak, untuk tombol "Lihat Menu" |
| `gambar`, `status` | Foto utama dan status aktif |

**Halaman yang perlu dibuat:** `/tempat/[slug]` harus membaca `slug` dari URL dan mengambil data
dari API. Saat ini parameternya diabaikan.

### 11.3 Fasilitas Olahraga

**Data pada mockup.** Halaman `/olahraga` memuat search dengan placeholder "Cari GOR, Lapangan,
Kolam Renang...", empat kartu kategori beserta jumlahnya (Bulutangkis 24 GOR, Futsal 18 Lapangan,
Kolam Renang 12 Lokasi, Basket 8 Lapangan), lalu section "Terdekat dan Populer" dengan dua item
(GOR Graha Bulutangkis di Pamulang pada jarak 1,2 km, dan Taman Kota 1 BSD di Serpong pada jarak
3,5 km). Setiap kartu memuat rating, lokasi, jarak, dan tombol Booking.

**Kolom yang dibutuhkan.**

| Kolom | Keterangan |
|---|---|
| `nama`, `slug`, `deskripsi` | Identitas dan ringkasan |
| `jenis` | enum: `gor_bulutangkis`, `lapangan_futsal`, `kolam_renang`, `lapangan_basket`, `gym` |
| `alamat`, `latitude`, `longitude`, `kecamatan_id` | Lokasi |
| `kapasitas`, `tarif_sewa` | Kapasitas dan tarif per jam atau per sesi |
| `fasilitas` | JSON: Bola, raket, lapangan, tempat mandi |
| `rating`, `gambar`, `status` | Rating, foto, dan status aktif |

**Catatan.** **Keputusan: Booking tidak dibuat.** Tombol Booking dihilangkan dari mockup; kartu
cukup berisi rating, lokasi, dan jarak yang mengarah ke halaman detail. Kolom `kapasitas` dan
`tarif_sewa` tetap disiapkan pada skema data untuk pengembangan lanjutan.

### 11.4 Layanan Kesehatan

**Data pada mockup.** Halaman `/kesehatan` memuat banner darurat dengan tombol Panggil 119, dua
kartu pintasan (Antrean RSUD untuk cek kuota dan daftar poli, serta Cek Puskesmas untuk jadwal dan
layanan terdekat), lalu section "Daftar RSUD dan Puskesmas" dengan tiga item: RSUD Kota Tangerang
Selatan (Rumah Sakit Umum, Pamulang, buka 24 jam), Puskesmas Pondok Aren (Puskesmas Rawat Inap,
Pondok Aren, buka 24 jam), dan RSUD Pondok Aren (Rumah Sakit Tipe C, Pondok Aren, buka 24 jam).

**Kolom yang dibutuhkan.**

| Kolom | Keterangan |
|---|---|
| `jenis` | enum: `apotek`, `rumah_sakit`, `puskesmas`, `klinik`, `posyandu` — **apotek menjadi pembeda utama** |
| `nama`, `slug`, `deskripsi` | Identitas dan ringkasan |
| `alamat`, `latitude`, `longitude`, `kecamatan_id` | Lokasi |
| `telepon` | Nomor kontak, dipakai pada kartu apotek |
| `jam_buka`, `jam_tutup`, `layanan_24_jam` | Jam praktik |
| `fasilitas` | JSON: IGD, poliklinik, laboratorium, ambulans, parkir |
| `apotek` | JSON khusus jenis `apotek`: jam buka, layanan resep, nomor izin |
| `status_antrean` | JSON atau kolom khusus untuk antrean RSUD, bila masih dipakai |

**Catatan penting.** Dua fitur yang sudah tercantum di mockup — Antrean RSUD dan Cek Puskesmas —
merupakan data dari **sistem milik instansi pemerintah**, bukan milik platform ini. Proposal
sendiri menyatakan integrasi API instansi di luar lingkup penelitian. Jadi kedua fitur itu sebaiknya
dibuat sebagai **data yang dikelola manual oleh administrator**, dengan catatan batas yang jelas di
Bab 4, bukan sebagai integrasi otomatis.

**Gap.** Apotek belum ada di mockup, padahal termasuk fitur yang diminta. Perlu tambahan section
apotek beserta chip filter jenis layanan.

### 11.5 Laporan Warga

**Alur lima langkah sesuai proposal:**

```
Temukan Masalah  ->  Kirim Foto dan GPS  ->  Verifikasi Admin  ->  Diproses Admin  ->  Selesai
```

**Data pada mockup.** Halaman `/laporan` memuat tiga tab (Semua Aduan, Laporan Saya, Selesai), banner
Buat Laporan Baru, lalu feed kartu yang memuat foto profil pelapor, waktu, badge status, judul,
deskripsi, foto bukti, dan lokasi. Status yang terpakai pada mockup: **Menunggu**, **Diproses**,
**Selesai**.

Halaman `/laporan/buat` memuat lima kolom isian: bukti foto dengan dua aksi (Ambil Foto dan
Galeri, maksimum tiga foto), kategori laporan dengan empat opsi (Jalan Rusak atau Infrastruktur,
Sampah dan Kebersihan, Fasilitas Umum, Ketertiban dan Keamanan), judul laporan, deskripsi detail, dan
lokasi kejadian berupa input yang belum dapat diketik dengan tombol pembuka peta.

**Kolom yang dibutuhkan.**

| Kolom | Keterangan |
|---|---|
| `kode` | Nomor tiket unik, untuk pelacakan laporan oleh pelapor dan administrator |
| `kategori_id` | Foreign key ke `kategori`; empat kategori sesuai mockup |
| `judul`, `deskripsi` | Isi laporan |
| `pelapor_id` | Foreign key ke `users` |
| `latitude`, `longitude` | Geotagging titik kejadian |
| `status` | enum mengikuti transisi alur, lihat di bawah |
| `catatan_admin` | Catatan peninjauan dan penanganan dari administrator |

**Transisi status.**

| Dari | Ke | Pelaku |
|---|---|---|
| — | `baru` | Warga saat mengirim laporan |
| `baru` | `diverifikasi` atau `ditolak` | Administrator |
| `diverifikasi` | `diproses` | Administrator |
| `diproses` | `selesai` | Administrator |

**Halaman yang perlu dibuat:** detail laporan per kode untuk pelacakan status dan halaman
verifikasi untuk administrator.

**Tampilan publik:** feed publik (`GET /laporan`) dan section "Pantau Aduan Sekitar" pada beranda
hanya menampilkan laporan yang sudah berstatus `diverifikasi` ke atas.

**Gap.** Mockup memakai tiga status, sedangkan alur proposal punya lima langkah. Perbedaan
diselesaikan dengan memakai enum lima nilai (`baru`, `diverifikasi`, `ditolak`, `diproses`,
`selesai`), lalu memetakan tampilan: `baru` dan `diverifikasi` ditampilkan sebagai Menunggu,
`diproses` sebagai Diproses, dan `selesai` sebagai Selesai.

---

## 12. Matriks Peran Pengguna

Dua peran sesuai revisi proposal: administrator dan warga.

| Kemampuan | Administrator | Warga |
|---|---|---|
| Baca berita, tempat, olahraga, kesehatan | Ya | Ya |
| Kelola data tempat, olahraga, kesehatan | Ya | Tidak |
| Tulis dan ubah berita editorial | Ya | Tidak |
| Jalankan pengambilan berita otomatis | Ya | Tidak |
| Tinjau atau ubah ringkuman hasil AI | Ya | Tidak |
| Kirim laporan warga | Tidak | Ya |
| Verifikasi atau tolak laporan | Ya | Tidak |
| Ubah status menjadi Diproses atau Selesai | Ya | Tidak |
| Pantau laporan miliknya sendiri | Tidak | Ya |
| Kelola akun pengguna | Ya | Tidak |

---

## 13. Entitas Data dan Relasi

Sembilan entitas inti untuk tahap ini.

| Entitas | Kolom inti | Relasi |
|---|---|---|
| `users` | `nama`, `email`, `password`, `role` enum `admin`/`warga`, `telepon`, `aktif` | induk dari `berita.editor_id`, `laporan.pelapor_id` |
| `kategori` | `nama`, `slug`, `scope`, `ikon`, `urutan` | induk dari `berita.kategori_id`, `laporan.kategori_id` |
| `kecamatan` | `nama`, `slug`, `urutan` | induk dari `tempat`, `fasilitas_olahraga`, `layanan_kesehatan` |
| `tempat` | `jenis` enum `kafe`/`warkop`/`restoran`, `nama`, `slug`, `deskripsi`, `alamat`, koordinat, jam buka-tutup, `rentang_harga`, `rating`, `fasilitas` JSON, `menu` JSON, `gambar`, `status` | Many-to-one ke `kecamatan` |
| `fasilitas_olahraga` | `jenis`, `nama`, `slug`, `deskripsi`, alamat, koordinat, `kapasitas`, `tarif_sewa`, `fasilitas` JSON, `rating`, `gambar`, `status` | Many-to-one ke `kecamatan` |
| `layanan_kesehatan` | `jenis` enum `apotek`/`rumah_sakit`/`puskesmas`/`klinik`/`posyandu`, `nama`, `slug`, `deskripsi`, alamat, koordinat, `telepon`, jam buka-tutup, `layanan_24_jam`, `fasilitas` JSON, `apotek` JSON, `status` | Many-to-one ke `kecamatan` |
| `berita` | `tipe` enum `editorial`/`agregat`, `judul`, `slug`, `ringkasan`, `diringkas_oleh` enum `feed`/`ai`/`editor`, `konten`, `url_asli`, `sumber`, `sumber_url`, `gambar`, `status`, `published_at`, `fetched_at` | Many-to-one ke `kategori`, `kecamatan`, dan `users` sebagai penyunting |
| `berita_sumber` | `nama`, `jenis`, `feed_url`, `aktif`, `last_fetched_at`, `last_status`, `last_error`, `item_count` | Tidak berelasi; tabel pendukung agregasi |
| `laporan` | `kode`, `judul`, `deskripsi`, koordinat, `status`, `catatan_admin`, `diselesaikan_at` | Many-to-one ke `users` (pelapor), `kecamatan`, `kategori` |

Pendukung: `laporan_foto` (tabel anak dari `laporan`) untuk maksimal tiga bukti foto per laporan.

Tiga tabel direktori — `tempat`, `fasilitas_olahraga`, `layanan_kesehatan` — sengaja dibuat **sejajar**
karena tiga fitur tersebut saling bebas dan tidak butuh relasi satu sama lain. Penyatuan ketiganya
menjadi satu tabel direktori ditolak karena kolom akan banyak dan bisa jadi jarang terisi, sedangkan
biaya migrasinya lebih besar. Kolom yang mirip antar-ketiganya diakomodasi dengan JSON.

---

## 14. Kebutuhan Endpoint API

Semuanya di bawah prefiks `/api`. Status autentikasi menunjukkan apakah endpoint bisa diakses publik
atau memerlukan token.

**Berita**

| Endpoint | Auth | Keterangan |
|---|---|---|
| `GET /berita` | Publik | Filter kategori, kecamatan, sumber, kata kunci, tipe, cursor, per_page |
| `GET /berita/terbaru` | Publik | Dipakai section Kabar di beranda |
| `GET /berita/{slug}` | Publik | Detail berita |
| `GET /berita/meta` | Publik | Daftar kategori, kecamatan, sumber, beserta jumlahnya |
| `POST /admin/berita/refresh` | Admin | Pemicu `berita:fetch` |

**Tempat, fasilitas olahraga, layanan kesehatan** — pola sama untuk ketiganya:

| Endpoint | Auth |
|---|---|
| `GET /{fitur}` dengan filter, pencarian, cursor | Publik |
| `GET /{fitur}/{slug}` | Publik |
| `GET /{fitur}/meta` untuk daftar jenis dan kecamatan | Publik |
| `POST`, `PUT`, `DELETE` | Admin |

**Laporan warga**

| Endpoint | Auth | Keterangan |
|---|---|---|
| `GET /laporan` | Publik | Feed laporan terverifikasi (menjadi sumber section beranda) |
| `GET /laporan?mine=1` | Token | Laporan milik pengguna login (tab "Laporan Saya"); token Bearer dibaca via `auth('sanctum')->user()` karena route publik |
| `GET /laporan/meta` | Publik | Opsi form: kategori (`scope=laporan`) + daftar kecamatan |
| `GET /laporan/{kode}` | Pemilik, admin | Detail beserta timeline status |
| `POST /laporan` | Warga | Kirim laporan, multipart dengan foto dan koordinat |
| `GET /admin/laporan` | Admin | Daftar moderasi (semua status, cursor paginate + filter `status`) |
| `POST /laporan/{kode}/verifikasi` | Admin | Set `diverifikasi` atau `ditolak` — laporan `diverifikasi` tampil di feed dan beranda |
| `POST /laporan/{kode}/status` | Admin | Ubah menjadi `diproses` atau `selesai` |

**Autentikasi**

| Endpoint | Auth |
|---|---|
| `POST /auth/register` | Publik |
| `POST /auth/login` | Publik |
| `POST /auth/logout` | Token |
| `GET /auth/me` | Token |

---

## 15. Timeline Implementasi

Pemetaan ke tahapan pada proposal:

| Tahapan proposal | Isi pada tahap ini | Hasil |
|---|---|---|
| Tahap 2, minggu 1 | Konfigurasi Laragon, database, migration, seeder | Tabel siap |
| Tahap 2, minggu 2 | Endpoint berita, tempat, fasilitas olahraga | API publik untuk tiga fitur pertama |
| Tahap 2, minggu 3 | Endpoint layanan kesehatan, laporan warga, autentikasi Sanctum | Lima fitur terbackend penuh |
| Tahap 2, minggu 4 | Unit test dan feature test, dokumentasi API | Pengujian backend |
| Tahap 3 | Penyambungan beranda, wrapper halaman yang ada, token httpOnly | Halaman terhubung ke API |

**Urutan pengerjaan yang disarankan** — backend lebih dulu, karena seluruh halaman sudah ada dalam
bentuk statis dan hanya menunggu sumber data:

1. Perbaikan environment (Composer, ekstensi curl, MySQL)
2. `composer create-project` untuk `tangsel-api`, konfigurasi `.env`
3. Migration dan seeder untuk sembilan entitas
4. Endpoint untuk `tempat`, `fasilitas_olahraga`, `layanan_kesehatan` (paling sederhana, cepat jadi)
5. Modul Berita beserta service agregasi dan penjadwalan
6. Endpoint `laporan` beserta autentikasi dan transisi status
7. Penyambungan halaman frontend ke API
8. Pengujian menyeluruh

---

## 16. Gap dan Open Questions

### 16.1 Yang perlu diputuskan

| Nomor | Pertanyaan | Dampak |
|---|---|---|
| 1 | **Selesai (revisi):** sistem hanya memiliki **dua peran — administrator dan warga**; segment control tiga mode dihapus dari beranda, hak akses cukup dua ranah (publik + dashboard admin). | Desain beranda dan pemetaan hak akses final |
| 2 | Proposal menyebut "sembilan entitas data lokal" tanpa merincinya. Daftar sembilan entitas pada bagian 13 adalah tetapan sementara oleh penulis. Apakah sudah ada ERD sendiri yang perlu diikuti? | Menentukan migration dan relasi |
| 3 | **Selesai:** kafe dan warkop disatukan pada entitas `tempat` dengan pembeda kolom `jenis`. | Model data final |
| 4 | Link Google News tidak dapat diteruskan ke artikel asli di sisi server. Apakah tautan ke agregator dianggap cukup untuk tahap ini? | Menentukan pengalaman pengguna |

### 16.2 Gap implementasi yang teridentifikasi

| Gap | Bukti | Dampak |
|---|---|---|
| Halaman Berita belum ada | Tidak ada route `src/app/berita` | Fitur inti belum punya halaman |
| Halaman detail tempat mengabaikan `slug` | `src/app/tempat/[slug]/page.tsx` baris 4 | Halaman detail tidak akan menampilkan data berbeda per tempat |
| Apotek belum ada di mockup kesehatan | `src/app/kesehatan/page.tsx` hanya memuat RSUD dan Puskesmas | Fitur yang diminta belum terwakili |
| Tombol Booking tanpa halaman tujuan | `src/app/olahraga/page.tsx` | Navigasi buntu |
| Tiga status pada mockup versus lima langkah pada proposal | `src/app/laporan/page.tsx` | Perlu pemetaan status seperti dijelaskan pada bagian 11.5 |
| Belum ada halaman login | Tidak ada route login di `src/app` | Autentikasi belum tersedia |
| Belum ada dashboard administrator | Tidak ada route dashboard | Alur verifikasi dan proses laporan belum dapat dijalankan |
| Laporan tidak menyimpan kontak pelapor | `src/app/laporan/buat/page.tsx` | Admin tidak punya cara menghubungi pelapor; perlu kolom nomor telepon |
| Notifikasi real-time di luar lingkup | Sesuai proposal | Status laporan hanya dapat dipantau dengan memuat ulang halaman |

### 16.3 Catatan untuk penulisan laporan

Beberapa hal di atas dapat dijadikan bahan pembahasan pada Bab 4, khususnya:

- Justifikasi pemilihan RSS dibanding scraping HTML, beserta bukti uji pada bagian 3.
- Alasan pemisahan tiga tabel direktori (`tempat`, `fasilitas_olahraga`, `layanan_kesehatan`)
  alih-alih satu tabel dengan kolom JSON besar.
- Pemetaan status laporan dua lapis antara nilai enum di database dan label yang ditampilkan.
- Batas integrasi data instansi pemerintah (antrean RSUD) sebagai fitur yang dikelola manual.
- Analisis keberlanjutan platform (biaya operasional free tier, regenerasi konten otomatis
  RSS + AI, ketergantungan pada API pihak ketiga) sebagai bahan Bab 4.
- Kualitas ringkuman AI dibandingkan artikel sumber aslinya sebagai bukti kelayakan modul Berita.