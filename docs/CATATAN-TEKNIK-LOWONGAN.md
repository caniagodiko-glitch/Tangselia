# Catatan Teknis — Modul Lowongan Kerja & Magang

Dokumen acuan teknis untuk modul **Lowongan Kerja & Magang** pada TANGSEL LIA.
Modul ini **menggantikan** modul Laporan Warga yang sebelumnya ada di aplikasi.

Dokumen ini dipakai untuk:

1. Menjawab pertanyaan dosen/penguji saat Sidang.
2. Mempertahankan konsistensi saat menambah sumber, kategori, atau status baru.
3. Menulis Bab 2 (Landasan Teori), Bab 3 (Metodologi), dan Bab 4 (Hasil) secara akurat.

> Dokumen induk: `CATATAN-TEKNIK-BERITA.md` (modul berita) dan
> `CATATAN-TEKNIK-DIREKTORI-TEMPAT.md` (modul direktori).

---

## Daftar Isi

1. [Mengapa modul ini diganti](#1-mengapa-modul-ini-diganti)
2. [Arsitektur data](#2-arsitektur-data)
3. [Sumber data lowongan](#3-sumber-data-lowongan)
4. [Alur pemrosesan](#4-alur-pemrosesan)
5. [Penyaringan wilayah](#5-penyaringan-wilayah)
6. [Klasifikasi](#6-klasifikasi)
7. [Kurasi dan status](#7-kurasi-dan-status)
8. [Endpoint API](#8-endpoint-api)
9. [Lapisan frontend](#9-lapisan-frontend)
10. [Konfigurasi](#10-konfigurasi)
11. [Perintah artisan](#11-perintah-artisan)
12. [Pengujian](#12-pengujian)
13. [Data nyata saat ini](#13-data-nyata-saat-ini)
14. [Batasan dan gap yang diketahui](#14-batasan-dan-gap-yang-diketahui)
15. [Cara menjawab pertanyaan penguji](#15-cara-menjawab-pertanyaan-penguji)

---

## 1. Mengapa modul ini diganti

Modul awal aplikasi berupa **Laporan Warga**: warga mengirim laporan lingkungan
lengkap foto dan koordinat GPS, lalu admin memverifikasi, memproses, dan
menutup statusnya.

Setelah evaluasi, modul tersebut dihapus dan diganti
**Lowongan Kerja & Magang** dengan alasan:

| Aspek | Laporan Warga (lama) | Lowongan Kerja & Magang (baru) |
|---|---|---|
| Arah data | warga → aplikasi (push) | sumber eksternal → aplikasi (pull) |
| Kebutuhan AI | tidak ada (teks dari warga) | ekstraksi field terstruktur dari teks sumber |
| Alur | 5 langkah (Temukan Masalah → Selesai) | 2 tahap (Ambil dari Sumber → Curasi Administrator) |
| Peran | warga sebagai pelapor + admin | admin sebagai kurator + warga sebagai pembaca |
| Penyaringan wilayah | koordinat GPS dari pelapor | penanda wilayah pada judul/cuplikan lowongan |
| Masa berlaku | tidak ada (status manual) | kedaluwarsa otomatis 30 hari |

Konsekuensi teknisnya besar dan sudah diimplementasikan:

- Tabel `laporan` dan `laporan_foto` di-drop
  (migration `2026_10_22_104910_drop_laporan_tables.php`).
- Tabel `berita_sumber` diubah menjadi `sumber` dengan kolom
  `tipe enum('berita','lowongan')` supaya berita dan lowongan berbagi
  infrastruktur sumber RSS.
- `kategori.scope` berubah menjadi `enum('berita','lowongan')`;
  4 kategori dengan scope `laporan` dihapus, 6 kategori bidang pekerjaan
  (lowongan) dibuat.
- Nama rute, halaman, dan komponen frontend berubah total:
  `/laporan` → `/lowongan`, `/laporan/buat` → dihapus,
  `LaporanPanel` → `AdminLowonganPanel`.

---

## 2. Arsitektur data

### 2.1 Tabel `lowongan`

Kolom inti (mirror dari `berita` supaya pola akses data konsisten):

| Kolom | Tipe | Keterangan |
|---|---|---|
| `kode` | string unik | `LGN-000123`, dibuat berurutan |
| `slug` | string unik | slug judul, suffix bila bentrok |
| `judul` | text | judul lowongan setelah dirapikan |
| `judul_hash` | string(40) | SHA-1 judul ternormalisasi, untuk dedup lintas sumber |
| `posisi` | string nullable | nama jabatan, mis. "Sales Promotion Girl" |
| `perusahaan` | string nullable | nama perusahaan |
| `jenis` | enum | `magang` \| `karir` \| `part-time` |
| `deskripsi` | text nullable | ringkasan lowongan (sumber: cuplikan feed) |
| `kualifikasi` | text nullable | syarat pelamar |
| `cara_apply` | text nullable | cara melamar |
| `url_asli` | string | tautan sumber (maks 500 karakter) |
| `sumber` | string | nama sumber/penerbit |
| `sumber_url` | string nullable | origin situs sumber |
| `status` | enum | `baru` \| `tayang` \| `ditolak` \| `kedaluwarsa` |
| `relevan` | boolean | penanda layak ditampilkan |
| `catatan_admin` | text nullable | alasan tolak/catatan kurator |
| `tayang_at` | timestamp nullable | diisi saat status menjadi `tayang` |
| `kedaluwarsa_at` | timestamp nullable | batas tayang (default +30 hari) |
| `published_at` | timestamp | tanggal publikasi di sumber |
| `fetched_at` | timestamp | waktu pengambilan dari feed |
| `sumber_id` | FK | ke `sumber.id` |
| `kategori_id` | FK nullable | ke `kategori.id` (scope `lowongan`) |
| `kecamatan_id` | FK nullable | ke `kecamatan.id` |

### 2.2 Scope model

`Lowongan` berelasi ke `Sumber`, `Kategori` (scope `lowongan`), dan `Kecamatan`.
Scope `Lowongan` dipakai pada model `Kategori` agar kategori berita dan
kategori bidang pekerjaan lowongan tidak saling bercampur.

---

## 3. Sumber data lowongan

### 3.1 Strategi hibrida

Feed RSS langsung dari job board (LinkedIn Jobs, Glints, Karir.com) **tidak bisa
diambil** — server membalas `403`/`404` untuk robot. Karena itu dipakai dua
jalur:

| Jalur | Mekanisme | Kelebihan | Keterbatasan |
|---|---|---|---|
| **Job board** | Google News RSS + operator `site:` | isi lowongan utuh, judul terstruktur | `<link>` adalah redirect JS `news.google.com` |
| **Media lokal** | Bing News RSS (kueri polos) | `<link>` menunjuk artikel asli langsung | teks berupa ringkasan berita, bukan lowongan utuh |

Kueri job board (dari `SumberSeeder`):

```
https://news.google.com/rss/search?q=site%3Alinkedin.com%2Fjobs+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid
https://news.google.com/rss/search?q=site%3Aglints.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid
https://news.google.com/rss/search?q=site%3Akarir.com+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid
```

Kueri media lokal:

```
https://www.bing.com/news/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&format=rss
https://www.bing.com/news/search?q=%22lowongan+kerja%22+BSD&format=rss
https://www.bing.com/news/search?q=loker+%22Tangerang+Selatan%22&format=rss
https://www.bing.com/news/search?q=informasi+lowongan+kerja+Tangerang+Selatan&format=rss
```

Kueri umum (Google News, tanpa `site:`):

```
https://news.google.com/rss/search?q=%22lowongan+kerja%22+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid
https://news.google.com/rss/search?q=magang+%22Tangerang+Selatan%22&hl=id&gl=ID&ceid=ID%3Aid
https://news.google.com/rss/search?q=%28BSD+OR+Bintaro+OR+Serpong+OR+Pamulang%29+lowongan&hl=id&gl=ID&ceid=ID%3Aid
```

Total **10 sumber lowongan** aktif di seeder.

### 3.2 Kenapa Bing News tidak memakai `site:`

Bing News RSS mengabaikan operator `site:` — kueri `site:glints.com` tetap
mengembalikan berita umum. Google News RSS justru memperlakukannya dengan benar.
Karena itu pembagian sumber dibuat berdasarkan domain job board, bukan berdasarkan
jenis kueri.

### 3.3 Keterbatasan tautan Google News

`<link>` dari Google News RSS berbentuk redirect JS opaque
(`https://news.google.com/rss/articles/CBMi...`). Percobaan resolve memakai
endpoint `batchexecute` menghasilkan HTTP 400, sehingga tautan asli tidak bisa
dipulihkan.

**Keputusan:** tautan Google News **diterima apa adanya** sebagai `url_asli`,
karena:
1. tautan itu tetap mengarahkan pengguna ke artikel aslinya saat dibuka di browser;
2. sumber Bing News tetap menyediakan tautan langsung untuk verifikasi silang.

### 3.4 Pemisahan sumber berita vs lowongan

Kolom `sumber.tipe` membedakan dua kelompok sumber:

- `berita` → 9 sumber (Google News ×2, Bing News, ANTARA Banten, TangerangNews,
  BantenNews, PosBanten, SinarBanten, ANTARA Nasional)
- `lowongan` → 10 sumber (lihat §3.1)

Kedua tabel memakai service `FeedParser` yang sama, dan `LowonganFetcher`
memfilter `where('tipe', 'lowongan')` agar tidak ada tumpang tindih.

---

## 4. Alur pemrosesan

```
Sumber RSS (tipe lowongan)
        │
        ▼
LowonganFetcher::run()
        │  unduh feed (timeout 15 dtk, UA TANGSEL-LIA-Bot/1.0)
        │  ambil maksimal 20 item per sumber (config lowongan.maks_item_per_sumber)
        ▼
FeedParser::parse()  →  judul, link, pub_date, ringkasan, gambar, source
        │
        ▼
DEDUP LAPIS 1 → url_asli sudah ada di DB?          → lewati
        ▼
AIEkstraksiLowongan::rapikanJudul() → judul bersih + judul_hash (SHA-1)
        │
        ▼
DEDUP LAPIS 2 → judul_hash sudah ada di DB?        → lewati
        ▼
Ambil isi artikel (ArticleContentFetcher, hormati robots.txt)
        │  teks ini HANYA jadi bahan baca AI, tidak disimpan utuh
        ▼
AIEkstraksiLowongan::ekstrak()  ── gagal / tanpa API key ──► parseDariJudul()
        │  posisi, perusahaan, deskripsi, kualifikasi, cara_apply
        │  fallback: rapikanHasil() untuk sumber umum
        ▼
LowonganClassifier::classify() → kategori_id, kecamatan_id, jenis
        │
        ▼
LowonganWilayah::layak()  +  LowonganFetcher::relevan()  → boolean relevan
        │
        ▼
SIMPAN dengan status = "baru"  (menunggu kurasi administrator)
```

### 4.1 Peran AI

AI **tidak menentukan** apa itu lowongan, tidak menentukan jenis lowongan, dan
tidak mengarang isi. Tugasnya satu saja: memindahkan fakta yang sudah tertulis
pada judul dan isi artikel ke kolom terstruktur.

Prompt-nya ada di `config/lowongan.php` (`prompt_ekstraksi`) dan menegaskan:

> "HANYA ambil fakta yang tertulis pada teks. Bila suatu informasi tidak ada,
> isi dengan string kosong. Jangan mengarang, jangan menambahkan informasi dari
> luar teks."

Konsekuensi yang disengaja:

- `posisi`, `kualifikasi`, dan `cara_apply` **boleh kosong**. Itu lebih jujur
  daripada mengisi data fiktif.
- `jenis` **tidak pernah** diambil dari AI karena penentuannya lebih andal
  secara deterministik (§6).

### 4.2 Peran parser cadangan

`AIEkstraksiLowongan::parseDariJudul()` membaca pola judul job board yang
sangat terstruktur, misalnya:

> `Loker Spg Peralatan Rumah Tangga di Tangerang Selatan`

Diperlakukan sebagai: **posisi** = "Spg Peralatan Rumah Tangga",
perusahaan = kosong, jenis = "karir".

Perhatikan bahwa frasa **"Loker X di Y"** berarti posisi `X`, bukan nama
perusahaan. Ini diverifikasi langsung dari judul feed Glints.

Untuk sumber umum (bukan job board), pola judul bisa memotong kalimat, sehingga
hasilnya dibersihkan dulu oleh `rapikanHasil()`: potongan judul yang memuat
kata keterangan (`lowongan`, `loker`, `rekrutmen`, `pelamar`, `penempatan`,
`perusahaan`, ...) dibuang supaya kolom `posisi` dan `perusahaan` tidak
menyimpan kalimat yang tidak informatif.

### 4.3 Deskripsi

Sumber kebenaran deskripsi adalah **cuplikan feed** (`$cuplikan`), bukan isi
artikel. Alasannya: teks halaman masih memuat menu navigasi, footer, dan
iklan, sehingga lebih kotor daripada ringkasan yang ditulis penerbit.

Cuplikan yang hanya mengulang judul (+ nama sumber) dibuang oleh
`bersihkanDeskripsi()`, karena tidak menambah informasi apa pun.

Batas panjang: `config('lowongan.panjang_deskripsi')` = 700 karakter.

---

## 5. Penyaringan wilayah

Inilah bagian yang paling sering ditanyakan, karena sederhana secara kode tetapi
menentukan kualitas data.

### 5.1 Masalah

Kueri `"lowongan kerja" "Tangerang Selatan"` di Google News **tetap** mengembalikan
berita lowongan dari **Kota Tangerang** — keduanya berbagi nama daerah
("Tangerang"). Lowongan di Jakarta, Bandung, atau Surabaya juga kadang
bocor masuk.

### 5.2 Solusi: `LowonganWilayah`

`app/Services/LowonganWilayah.php` menyediakan tiga method:

| Method | Arti |
|---|---|
| `menyebutTangsel(string $teks): bool` | teks memuat penanda wilayah Tangsel |
| `menunjukWilayahLain(string $teks): bool` | teks menunjuk kota/wilayah lain |
| `layak(string $teks, bool $wajibSebutWilayah = true): bool` | keputusan akhir |

### 5.3 Aturan "penanda positif menang"

```php
public function layak(string $teks, bool $wajibSebutWilayah = true): bool
{
    if ($this->menyebutTangsel($teks)) {
        return true;                    // ← positif menang
    }

    if ($wajibSebutWilayah) {
        return false;                   // sumber umum wajib menyebut Tangsel
    }

    return ! $this->menunjukWilayahLain($teks);
}
```

Alasannya: banyak lowongan di Tangsel juga menyebut "Jabodetabek" atau
"Jakarta" (misal lowongan dengan lokasi kerja BSD namun basis di Jakarta).
Bila penanda kota lain menang, lowongan yang sah ikut terbuang.

Parameter `$wajibSebutWilayah` dibedakan per jenis sumber:

| Jenis sumber | Nilai | Efek |
|---|---|---|
| Job board (LinkedIn/Glints/Karir) | `false` | tidak wajib menyebut Tangsel, tapi **ditolak** bila secara eksplisit menunjuk kota lain |
| Sumber umum (media lokal) | `true` | wajib menyebut penanda Tangsel |

### 5.4 Daftar penanda

**Penanda wilayah (positif)** — `config('lowongan.penanda_wilayah')`:

```
tangerang selatan, tangsel, bsd, ciputat, pamulang, serpong, setu, cisauk, legok, curug
```

**Penanda wilayah lain (negatif)** — `config('lowongan.penanda_wilayah_lain')`:

```
kota tangerang, pemkot tangerang, kotatangerang, jakarta, bandung, surabaya,
medan, makassar, semarang, yogyakarta, denpasar, samarinda, balikpapan,
pekanbaru, padang, manado, palembang, Banjarmasin, pontianak, serang, bogor,
bekasi, Depok
```

Pencocokan memakai **kata utuh** (`\b...\b`), bukan substring. Kasus khusus:
frasa `kota tangerang` hanya dianggap wilayah lain bila **tidak** diikuti
`selatan`, sehingga "Kota Tangerang Selatan" tidak salah klasifikasi.
Aturan ini punya regex sendiri:

```php
preg_match('/\bkota\s+tangerang\b(?!\s+selatan)/u', $huruf)
```

### 5.5 Lapisan kedua: penanda `relevan`

Penanda wilayah menjawab "di mana", tapi belum menjawab "apakah ini lowongan?".
Untuk itu `LowonganFetcher::relevan()` menerapkan dua aturan:

1. Sumber **job board** → selalu relevan (kuerinya sudah dibatasi wilayah dan
   isinya lowongan utuh).
2. Sumber **umum** → judulnya harus menyebut lowongan secara eksplisit
   (`lowongan`, `loker`, `hiring`, `rekrutmen`, `magang`, `pelamar`,
   `penempatan`, `career`, `jobs`, ...). Daftar ada di
   `config('lowongan.kata_kunci_lowongan')`.

Contoh yang otomatis tertolak: "Open Graph Title", "Gary Iskak Meninggal",
"Sampah Menumpuk", "Gramedia", "Traveloka" — semuanya berita umum yang muncul
di feed karena kebetulan cocok dengan kueri.

---

## 6. Klasifikasi

### 6.1 Jenis lowongan

Nilai `jenis` hanya boleh salah satu dari `magang`, `karir`, `part-time`.

Penentuannya **deterministik** dari peta kata kunci
(`config('lowongan.peta_jenis')`), bukan dari AI:

| Jenis | Kata kunci pemicu |
|---|---|
| `magang` | magang, internship, intern, trainee, magangan, praktik, stagiaire, kkn, skripsi, sales trainee |
| `part-time` | part time, part-time, parttime, freelance, freelancer, sessional, gig, ojek, harian |
| `karir` | (nilai bawaan bila tidak ada kata kunci lain) |

Mengapa tidak pakai AI: hasil AI bisa berubah-ubah tiap kali model diperbarui,
sehingga sulit diuji dan ditelusuri. Peta kata kunci bisa
diuji satu per satu di `tests/Unit/`.

### 6.2 Bidang pekerjaan (kategori)

Kategori lowongan memakai `kategori` dengan `scope = 'lowongan'`. Enam bidang:

| Kategori | Contoh kata kunci |
|---|---|
| Teknologi dan Informasi | software, developer, programmer, data, analis, designer, sistem, jaringan, digital, engineer, machine learning, cyber, ui ux, devops, quality assurance |
| Administrasi dan Keuangan | administrasi, admin, staf, staff, office, kantor, keuangan, finance, akuntansi, hrd, sdm, legal, hukum, sekretaris, purchasing, payroll, pajak, spv, koordinator, konsultan |
| Penjualan dan Ritel | sales, marketing, pemasaran, kasir, cashier, retail, toko, store, promosi, telemarketing, supervisor, spg, branch manager, property, usher, merchandising |
| Kuliner dan Perhotelan | kafe, cafe, coffee, resto, restaurant, barista, koki, chef, pelayan, waiter, hotel, hospitality, kuliner, catering, bistro, production, kitchen, bakery, gelato, bakso |
| Pendidikan dan Kesehatan | guru, dosen, pengajar, instruktur, tutor, klinik, dokter, perawat, bidan, farmasi, apotek, psikolog, pelatihan, teacher, sekolah, pendidikan, montessori |
| Lainnya | nilai bawaan (`config('lowongan.kategori_bawaan')`) |

Penentuan kategori memakai **skor jumlah kata kunci yang cocok** — kategori dengan
skor tertinggi yang menang. Bila tidak ada yang cocok, kategori bawaan dipakai.

### 6.3 Kecamatan

Kecamatan ditentukan dari nama kecamatan yang disebut pada teks
(daftar 7 kecamatan: Ciputat, Pamulang, Serpong, Setu, Cisauk, Legok, Curug).
Bila tidak ada yang disebut, `kecamatan_id` dibiarkan `null`.

---

## 7. Kurasi dan status

### 7.1 Alur

```
            ┌──────────┐
   fetch ──►│   baru   │  (menunggu kurasi)
            └────┬─────┘
                 │
        ┌────────┴────────┐
        ▼                 ▼
   ┌─────────┐      ┌──────────┐
   │ tayang  │      │ ditolak  │
   └────┬────┘      └─────┬────┘
        │                 │
        ▼                 │
 ┌──────────────┐        │
 │ kedaluwarsa  │        │
 └──────────────┘        │
                          │
        semua status ─────┘
                 │
                 ▼
          kembali ke "baru"
```

Status `kedaluwarsa` dibuat otomatis, bukan pilihan manual.

### 7.2 Tabel transisi

Ditetapkan di `AdminLowonganController::TRANSISI`:

| Dari | Boleh ke |
|---|---|
| `baru` | `tayang`, `ditolak` |
| `ditolak` | `baru` |
| `tayang` | `kedaluwarsa`, `baru` |
| `kedaluwarsa` | `baru` |

Transisi yang tidak ada di tabel → **HTTP 422** dengan pesan
`Transisi status dari "..." ke "..." tidak valid.`

### 7.3 Kedaluwarsa

Ketika lowongan menjadi `tayang`, backend mengisi:

- `tayang_at` = now
- `published_at` = now (kalau belum ada)
- `kedaluwarsa_at` = now + `config('lowongan.kedaluwarsa_hari')` (default **30 hari**)

Perintah `lowongan:expire` memindahkan lowongan yang `kedaluwarsa_at <= now`
dari `tayang` ke `kedaluwarsa`. Lowongan bertanda `kedaluwarsa` **tidak lagi
ditampilkan** di halaman publik karena scope `tayang()` hanya melihat status
`tayang` dengan `kedaluwarsa_at` kosong atau masih di masa depan.

### 7.4 Boole `relevan`

Menjadi sinyal kedua bagi kurator: lowongan `relevan = false` adalah kandidat
ditolak. Panel admin menampilkan penghitung per status dan filter
`semua | baru | tayang | ditolak | kedaluwarsa`.

---

## 8. Endpoint API

### 8.1 Publik

| Method | Rute | Fungsi |
|---|---|---|
| GET | `/api/lowongan` | daftar + filter `jenis`, `kategori`, `kecamatan`, `q`, `per_page`, `cursor` |
| GET | `/api/lowongan/terbaru` | lowongan terbaru untuk beranda |
| GET | `/api/lowongan/{slug}` | detail satu lowongan |
| GET | `/api/lowongan/meta` | pilihan filter: jenis, kategori (scope lowongan), kecamatan |

Semua endpoint publik hanya menampilkan lowongan berstatus `tayang` yang belum
kedaluwarsa.

### 8.2 Admin (butuh `auth:sanctum` + `role:admin`)

| Method | Rute | Fungsi |
|---|---|---|
| GET | `/api/admin/lowongan` | daftar untuk kurasi + filter status/jenis/relevan/q |
| GET | `/api/admin/lowongan/meta` | kategori bidang + kecamatan untuk form |
| PUT | `/api/admin/lowongan/{id}` | koreksi field hasil ekstraksi **dan/atau** ubah status |
| DELETE | `/api/admin/lowongan/{id}` | hapus lowongan yang tidak relevan |

Body `PUT` diterima: `posisi`, `perusahaan`, `jenis`, `deskripsi`, `kualifikasi`,
`cara_apply`, `kategori_id`, `kecamatan_id`, `relevan`, `status`, `catatan_admin`.

---

## 9. Lapisan frontend

### 9.1 Halaman publik

| Rute | Isi |
|---|---|
| `/lowongan` | daftar lowongan + filter jenis, bidang pekerjaan, kecamatan |
| `/lowongan/[slug]` | detail: posisi, perusahaan, jenis, deskripsi, kualifikasi, cara melamar, sumber, tautan ke artikel asli |

Beranda menampilkan section **"Lowongan Terbaru"** yang mengambil
`GET /lowongan/terbaru`.

### 9.2 Panel kurasi

`src/components/admin/AdminLowonganPanel.tsx` — komponen klien yang:

- mengambil daftar lewat proxy admin;
- menyaring per status dengan tombol;
- mengoreksi field (posisi, perusahaan, jenis, kategori, kecamatan, deskripsi,
  kualifikasi, cara melamar) lewat form inline;
- menjalankan aksi **Tayangkan**, **Tolak** (dengan `catatan_admin`),
  **Kembalikan ke Kurasi**, dan **Hapus**;
- menyediakan tautan "Cek Sumber" ke `url_asli` dan "Lihat Halaman" ke
  `/lowongan/[slug]`.

### 9.3 BFF dan keamanan token

Token Sanctum **tidak pernah** menyentuh JavaScript browser. Alurnya:

```
Browser ──cookie httpOnly tangsel_token──► Next.js Route Handler
                                                     │ Authorization: Bearer <token>
                                                     ▼
                                              Laravel /api/admin/*
```

Proxy generik ada di `src/app/api/admin/[...path]/route.ts` — cukup satu file
untuk menutup semua endpoint admin (`/api/admin/lowongan/*`, `/api/admin/berita/*`,
dan seterusnya). Otorisasi sesungguhnya tetap ditegakkan backend lewat
middleware `auth:sanctum` + `role:admin`; proxy hanya menyalin header.

### 9.4 Navigasi

| Bekas | Sekarang |
|---|---|
| nav "Laporan" / "Lapor" (mobile) | "Lowongan" |
| CTA accent "Buat Laporan" | dihapus |
| footer "Laporan Warga" | "Lowongan Kerja" |

---

## 10. Konfigurasi

Semua parameter modul ada di `config/lowongan.php`:

| Kunci | Nilai | Arti |
|---|---|---|
| `kedaluwarsa_hari` | `30` | masa tayang sebelum kedaluwarsa |
| `kode_prefix` | `'LGN'` | prefix kode lowongan |
| `maks_item_per_sumber` | `20` | batas item per sumber per satu kali pengambilan |
| `ambil_isi_artikel` | `true` | ambil isi artikel sebagai bahan baca AI |
| `panjang_deskripsi` | `700` | batas karakter deskripsi |
| `prompt_ekstraksi` | teks | prompt Gemini |
| `peta_kategori` | array | kata kunci → bidang pekerjaan |
| `kategori_bawaan` | `'lainnya'` | slug kategori cadangan |
| `peta_jenis` | array | kata kunci → jenis lowongan |
| `kata_kunci_lowongan` | array | penanda judul yang benar-benar lowongan |
| `penanda_wilayah` | array | penanda wilayah Tangsel (positif) |
| `penanda_wilayah_lain` | array | penanda kota lain (negatif) |
| `kata_keterangan` | array | kata yang menandai potongan kalimat, bukan nama |
| `domain_job_board` | array | domain yang dikenali sebagai job board |

Kunci AI ada di `config/ai.php` (`config('ai.key')`).

---

## 11. Perintah artisan

```bash
# Ambil lowongan dari semua sumber RSS tipe lowongan
php artisan lowongan:fetch

# Ambil dari satu sumber saja
php artisan lowongan:fetch --source="Glints"

# Tandai lowongan yang masa tayangnya habis
php artisan lowongan:expire
```

Keluarannya:

```
Sumber: 9 | Lowongan baru: 77 | Lewat (duplikat/kosong): 214 | Sumber gagal: 0
```

Penjadwalan produksi (belum diaktifkan) memakai `Schedule::command('lowongan:fetch')->hourly()`
dan `Schedule::command('lowongan:expire')->daily()` di `routes/console.php`.

---

## 12. Pengujian

```
php artisan test --compact
→ 152 passed (554 assertions)
```

File uji yang relevan:

| File | Cakupan |
|---|---|
| `tests/Unit/LowonganWilayahTest.php` | aturan wilayah: positif menang, kota lain ditolak, `Kota Tangerang` vs `Kota Tangerang Selatan` |
| `tests/Feature/LowonganTest.php` | endpoint publik: filter jenis/kategori/kecamatan, status `tayang` saja |
| `tests/Feature/AdminLowonganTest.php` | alur kurasi, transisi tidak valid → 422, guard admin |
| `tests/Feature/AIEkstraksiLowonganTest.php` | ekstraksi JSON + fallback `parseDariJudul()` + `rapikanHasil()` |

Pengujian tidak pernah memanggil Gemini API sungguhan — respons di-mock.

---

## 13. Data nyata saat ini

```
lowongan          : 77   (67 tayang / 10 ditolak)
kategori lowongan : 6
sumber            : 19   (9 berita + 10 lowongan)
```

Sebaran kategori setelah kurasi:

| Kategori | Jumlah |
|---|---|
| Lainnya | 38 |
| Administrasi dan Keuangan | 14 |
| Pendidikan dan Kesehatan | 11 |
| Penjualan dan Ritel | 8 |
| Teknologi dan Informasi | 5 |
| Kuliner dan Perhotelan | 1 |

10 lowongan yang ditolak memang benar-benar tidak relevan (judul berita umum,
bukan lowongan).

---

## 14. Batasan dan gap yang diketahui

| Batasan | Dampak | Rencana |
|---|---|---|
| `GEMINI_API_KEY` kosong di `.env` | ekstraksi AI mati, `posisi`/`kualifikasi`/`cara_apply` sebagian besar `null` | isi key, jalankan ulang `lowongan:fetch` |
| Tautan Google News tidak bisa di-resolve | `url_asli` untuk 3 sumber job board berupa redirect opaque | pertahankan; sumber Bing News menyediakan tautan langsung |
| Feed Karir.com tidak menyebut posisi di judul | kolom `posisi` kosong untuk lowongan dari sumber itu | parser AI akan mengisinya setelah key tersedia |
| Kategori "Lainnya" masih dominan (38/77) | peta kata kunci belum mencakup semua variasi | tambah kata kunci per kategori |
| Tidak ada pagination di UI daftar berita | hanya 12 berita yang terjangkau | belum dikerjakan |

**Gap yang belum dikerjakan:**

- Panel CRUD editorial berita (admin belum bisa menambah/mengubah berita manual).
- Filter admin lowongan memakai `per_page` default 20 tanpa UI pagination.
- Notifikasi lowongan baru (butuh push/email).
- Pencarian lowongan di dalam halaman (hanya filter dropdown).

---

## 15. Cara menjawab pertanyaan penguji

**"Kenapa tidak pakai API job board resmi?"**
Feed RSS langsung dari job board membalas 403/404 untuk robot. Karena itu
dipakai pencarian Google News dengan operator `site:` agar halaman lowongan
ikut terindeks, ditambah Bing News RSS yang menautkan artikel asli langsung.
Ini tradeoff yang terdokumentasi, bukan kelalaian.

**"Kenapa AI tidak menentukan jenis lowongan?"**
Supaya hasilnya deterministik dan bisa diuji. Peta kata kunci di
`config/lowongan.php` bisa diuji satu per satu; output model bahasa tidak.

**"Bagaimana memastikan lowongan benar-benar di Tangsel?"**
Dua lapis. Pertama, penyaringan wilayah berbasis penanda teks dengan aturan
positif-menang; kedua, penanda `relevan` yang menolak judul yang bukan lowongan
pada sumber umum. Setelah itu tetap ada kurasi administrator sebelum tayang.

**"Kenapa tidak menyimpan isi artikel lengkap?"**
Hanya cuplikan yang disimpan. Isi artikel diambil setelah memeriksa
`robots.txt` dan hanya dipakai sebagai bahan baca AI, lalu dibuang. Ini
menghormati hak penerbit dan menekan ukuran basis data.

**"Kenapa ada status kedaluwarsa?"**
Lowongan punya masa berlaku singkat. Tanpa batas waktu, portal akan menampilkan
lowongan yang sudah lewat. `kedaluwarsa_at` diisi otomatis 30 hari sejak
tayang, dan `lowongan:expire` memindahkannya ke status `kedaluwarsa`.

**"Bagaimana warga tahu lowongan itu masih berlaku?"**
Setiap lowongan menampilkan sumber dan tautan ke artikel aslinya, sehingga
pengguna bisa memverifikasi ke sumber aslinya secara langsung.
