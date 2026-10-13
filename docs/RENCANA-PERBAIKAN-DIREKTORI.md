# Rencana Perbaikan Direktori Tempat — "Opsi B" (Gratis-Legal)

> Status: **AKTIF** — dicetuskan 2026-10-13.
> Pilihan: **Opsi B** (gratis & legal, ber-atribusi). Keputusan pengguna setelah
> membandingkan dengan *scraping Google Maps* (ditolak: melanggar ToS, rapuh,
> inkonsisten dengan Bab 3). Modul **Google Places API** tetap tersedia sebagai
> opsi-C standby (backend sudah siap, tinggal isi `GOOGLE_MAPS_API_KEY`).

---

## Ringkasan Target Akhir (janji yang jujur)

| Elemen | Cakupan akhir |
|---|---|
| Deskripsi | **100%** — AI (Gemini), situs bisnis, Wikipedia, template OSM |
| Fasilitas | Sebagian besar (OSM deep + situs + admin + warga) |
| Menu | Tempat populer + isi admin + foto menu warga (±50–100) |
| Foto | ±100 bulan pertama, tumbuh via admin & warga |
| Rating | Dari ulasan warga (data sendiri) |

Sumber data sah: OpenStreetMap (ODbL),Wikipedia (CC BY-SA 4.0), situs resmi
bisnis (dengan izin/robots + atribusi), ulasan & foto warga (data sendiri).

---

## F0 — Git Safety

- [ ] Init repo git tunggal di `C:\laragon\www\tangselia` (mencakup `tangsel-api`, `tangsel-app`, `docs`)
- [ ] Buang `.git` lama di `tangsel-app` (cuma 1 commit scaffold)
- [ ] `.gitignore` root: `vendor/`, `node_modules/`, `.env`, `.next/`, `public/storage`, log
- [ ] Commit pertama seluruh kondisi kerja saat ini (snapshot)

## F1 — Backend Data (Laravel)

- [ ] Migration: kolom `website`, `telepon` (tempat & olahraga), `menu_ringkas`,
      `deskripsi_sumber`, `atribusi` JSON, `info_diunduh_pada` di 3 tabel direktori
- [ ] Migration + model + factory `ulasan` (rating warga + komentar + foto)
- [ ] `OsmImporter`: baca tag `website`/`contact:website`, `phone` (semua fitur),
      `brand`, `cuisine`; isi atribusi awal (ODbL); mode **perkaya** (isi kolom
      yang masih kosong saja, JANGAN timpa suntingan admin)
- [ ] `WikipediaFetcher` (`wiki:sync`): 34 RS dari daftar Wikipedia → backfill
      alamat + deskripsi intro (CC BY-SA)
- [ ] `InfoDirektoriFetcher` (`info:fetch`): crawler situs bisnis (±77) → foto
      sampul lokal + deskripsi meta + menu terbaik-mungkin (robots.txt dulu)
- [ ] `AIDeskripsiTempat` (`deskripsi:ai`): deskripsi kaya dari fakta nyata
      (Gemini; berhenti anggun bila key kosong)
- [ ] Controller CRUD admin terima `website`, `telepon`, `menu_ringkas`, `atribusi`
- [ ] Resource API tampilkan semua kolom baru + ringkasan ulasan warga
- [ ] Endpoint ulasan publik + `POST /api/ulasan` (auth warga, foto ≤3)
- [ ] Tes fitur semua service + endpoint; `vendor/bin/pint`; `php artisan test`

## F2 — Frontend Render (Next.js)

- [ ] Detail page: deskripsi/label sumber, galeri foto, map Leaflet, Tempat Terdekat, blok atribusi, badge "Belum terdata"
- [ ] Header & kartu list per-jenis dengan ikon/palette
- [ ] Form ulasan warga + tampil ulasan & rating rata-rata
- [ ] `npm run lint` + `npm run build`

## F3 — Pemeliharaan

- [ ] Dashboard admin: CRUD direktori (isi website/telepon/menu/manual/unggah foto)
- [ ] Dashboard admin: kelola ulasan (moderasi), statistik kelengkapan data
- [ ] Jadwal otomatis `info:fetch` + `wiki:sync` (`Schedule`)
- [ ] Verifikasi E2E + update `docs/RENCANA-PEKERJAAN.md`