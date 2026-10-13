/**
 * Klien API TANGSEL LIA.
 *
 * Semua request di sini menyasar endpoint backend Laravel (Sanctum).
 * Endpoint publik (direktori, berita, feed laporan) tidak butuh auth.
 * Endpoint yang butuh login (POST laporan, admin) akan ditambahkan
 * setelah strategi auth diputuskan.
 */

export const API_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

/** Query string untuk filter/pencarian/paginasi. */
export type Query = Record<string, string | number | boolean | null | undefined>;

export type Kecamatan = { nama: string; slug: string };

export type Tempat = {
  id: number;
  nama: string;
  slug: string;
  sumber: string;
  osm_id: string | null;
  google_place_id: string | null;
  jenis: string;
  deskripsi: string | null;
  alamat: string;
  latitude: number | string;
  longitude: number | string;
  kecamatan: Kecamatan | null;
  jam_buka: string | null;
  jam_tutup: string | null;
  jam_buka_teks: string | null;
  rentang_harga: string | null;
  rating: number | string | null;
  rating_kount: number | null;
  fasilitas: string[] | null;
  menu: unknown;
  gambar: string | null;
  status: string;
};

export type FasilitasOlahraga = {
  id: number;
  nama: string;
  slug: string;
  sumber: string;
  osm_id: string | null;
  google_place_id: string | null;
  jenis: string;
  deskripsi: string | null;
  alamat: string;
  latitude: number | string;
  longitude: number | string;
  kecamatan: Kecamatan | null;
  kapasitas: number | null;
  tarif_sewa: string | null;
  jam_buka_teks: string | null;
  fasilitas: string[] | null;
  rating: number | string | null;
  gambar: string | null;
  status: string;
};

export type LayananKesehatan = {
  id: number;
  nama: string;
  slug: string;
  sumber: string;
  osm_id: string | null;
  google_place_id: string | null;
  jenis: string;
  deskripsi: string | null;
  alamat: string;
  latitude: number | string;
  longitude: number | string;
  kecamatan: Kecamatan | null;
  telepon: string | null;
  jam_buka: string | null;
  jam_tutup: string | null;
  jam_buka_teks: string | null;
  layanan_24_jam: boolean | null;
  fasilitas: string[] | null;
  apotek: unknown;
  status: string;
};

export type Berita = {
  id: number;
  tipe: "editorial" | "agregat";
  judul: string;
  slug: string;
  ringkasan: string | null;
  diringkas_oleh: string | null;
  konten: string | null;
  url_asli: string | null;
  sumber: string | null;
  sumber_url: string | null;
  gambar: string | null;
  kategori: { id: number; nama: string; slug: string } | null;
  kecamatan: { id: number; nama: string; slug: string } | null;
  editor: { id: number; nama: string } | null;
  status: string;
  published_at: string | null;
  created_at: string | null;
};

export type Laporan = {
  id: number;
  kode: string;
  judul: string;
  deskripsi: string;
  latitude: number | string;
  longitude: number | string;
  status: string;
  kategori: { id: number; nama: string; slug: string; ikon: string | null } | null;
  kecamatan: { id: number; nama: string; slug: string } | null;
  foto: string[];
  pelapor: { id: number; nama: string } | null;
  catatan_admin: string | null;
  diselesaikan_at: string | null;
  created_at: string | null;
};

/** Hasil `cursorPaginate()` Laravel (Direktori, Berita, Laporan). */
export type Paginated<T> = {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: {
    path: string;
    per_page: number;
    next_cursor: string | null;
    prev_cursor: string | null;
  };
};

/** Pembungkus satu resource / koleksi tanpa paginasi. */
export type Item<T> = { data: T };

export type FilterJenis = { value: string; jumlah: number };
export type FilterKecamatan = { nama: string; slug: string; jumlah: number };
export type MetaDirektori = { jenis: FilterJenis[]; kecamatan: FilterKecamatan[] };
export type MetaBerita = {
  kategori: FilterKecamatan[];
  kecamatan: FilterKecamatan[];
  tipe: FilterJenis[];
  sumber: { nama: string; jumlah: number }[];
};
export type MetaLaporan = {
  kategori: { id: number; nama: string; slug: string; ikon: string | null }[];
  kecamatan: { id: number; nama: string; slug: string }[];
};
export type BeritaSumber = {
  id: number;
  nama: string;
  jenis: string;
  feed_url: string;
  aktif: boolean;
  last_fetched_at: string | null;
  last_status: string | null;
  last_error: string | null;
  item_count: number | null;
};

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

function buildUrl(path: string, query?: Query): string {
  const url = new URL(`${API_URL}${path}`);

  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value === undefined || value === null || value === "") {
        continue;
      }
      url.searchParams.set(key, String(value));
    }
  }

  return url.toString();
}

/**
 * Helper fetch tunggal ke API.
 *
 * - Selalu kirim header Accept: application/json.
 * - `cache: "no-store"` supaya data selalu fresh (belum pakai Cache Components).
 * - Error non-2xx dilempar sebagai `ApiError` (+ status) agar halaman bisa
 *   memanggil `notFound()` ketika 404.
 */
export async function apiFetch<T>(
  path: string,
  query?: Query,
  init?: RequestInit,
): Promise<T> {
  const response = await fetch(buildUrl(path, query), {
    cache: "no-store",
    ...init,
    headers: {
      Accept: "application/json",
      ...(init?.headers ?? {}),
    },
  });

  if (!response.ok) {
    let message = `Permintaan gagal (${response.status}).`;
    try {
      const body = (await response.json()) as { message?: string };
      if (body?.message) {
        message = body.message;
      }
    } catch {
      // Abaikan body yang bukan JSON, pakai pesan bawaan.
    }
    throw new ApiError(response.status, message);
  }

  return (await response.json()) as T;
}

// --- Direktori: Kuliner ---

export const getKuliner = (query?: Query) =>
  apiFetch<Paginated<Tempat>>("/kuliner", query);

export const getKulinerMeta = () =>
  apiFetch<Item<MetaDirektori>>("/kuliner/meta");

export const getKulinerDetail = (slug: string) =>
  apiFetch<Item<Tempat>>(`/kuliner/${slug}`);

// --- Direktori: Olahraga ---

export const getOlahraga = (query?: Query) =>
  apiFetch<Paginated<FasilitasOlahraga>>("/olahraga", query);

export const getOlahragaMeta = () =>
  apiFetch<Item<MetaDirektori>>("/olahraga/meta");

export const getOlahragaDetail = (slug: string) =>
  apiFetch<Item<FasilitasOlahraga>>(`/olahraga/${slug}`);

// --- Direktori: Kesehatan ---

export const getKesehatan = (query?: Query) =>
  apiFetch<Paginated<LayananKesehatan>>("/kesehatan", query);

export const getKesehatanMeta = () =>
  apiFetch<Item<MetaDirektori>>("/kesehatan/meta");

export const getKesehatanDetail = (slug: string) =>
  apiFetch<Item<LayananKesehatan>>(`/kesehatan/${slug}`);

// --- Berita ---

export const getBerita = (query?: Query) =>
  apiFetch<Paginated<Berita>>("/berita", query);

export const getBeritaTerbaru = (limit = 5) =>
  apiFetch<Item<Berita[]>>("/berita/terbaru", { limit });

export const getBeritaMeta = () => apiFetch<Item<MetaBerita>>("/berita/meta");

export const getBeritaDetail = (slug: string) =>
  apiFetch<Item<Berita>>(`/berita/${slug}`);

// --- Laporan (feed publik, status diverifikasi ke atas) ---

export const getLaporan = (query?: Query) =>
  apiFetch<Paginated<Laporan>>("/laporan", query);

export const getLaporanMeta = () => apiFetch<Item<MetaLaporan>>("/laporan/meta");
