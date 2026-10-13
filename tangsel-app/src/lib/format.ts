/**
 * Label & helper format tampilan (Bahasa Indonesia).
 */

export const LABEL_JENIS_TEMPAT: Record<string, string> = {
  kafe: "Kafe / Coffee Shop",
  warkop: "Warkop",
  restoran: "Restoran",
};

export const LABEL_JENIS_OLAHRAGA: Record<string, string> = {
  gor_bulutangkis: "GOR Bulutangkis",
  lapangan_futsal: "Lapangan Futsal",
  kolam_renang: "Kolam Renang",
  lapangan_basket: "Lapangan Basket",
  gym: "Gym / Fitness",
};

export const LABEL_JENIS_KESEHATAN: Record<string, string> = {
  apotek: "Apotek",
  rumah_sakit: "Rumah Sakit",
  puskesmas: "Puskesmas",
  klinik: "Klinik",
  posyandu: "Posyandu",
};

export const LABEL_TIPE_BERITA: Record<string, string> = {
  editorial: "Editorial",
  agregat: "Agregat",
};

export const LABEL_STATUS_LAPORAN: Record<string, string> = {
  baru: "Baru",
  diverifikasi: "Diverifikasi",
  ditolak: "Ditolak",
  diproses: "Diproses",
  selesai: "Selesai",
};

/** Ambil label dari map, fallback ke nilai mentah yang dirapikan. */
export function labelOf(map: Record<string, string>, value: string | null | undefined): string {
  if (!value) {
    return "-";
  }
  return map[value] ?? value;
}

/**
 * Ubah nilai rating jadi teks.
 *
 * Data hasil impor OSM mengirim rating `0` (belum ada penilaian), jadi
 * nilai nol/kosong ditampilkan sebagai "-" agar tidak muncul badge "0.0".
 */
export function ratingText(value: number | string | null | undefined): string {
  if (value === null || value === undefined || value === "") {
    return "-";
  }
  const numeric = Number(value);
  if (!Number.isFinite(numeric) || numeric <= 0) {
    return "-";
  }
  return numeric.toFixed(1);
}

/** Gabungkan jam buka/tutup (format HH:MM) jadi rentang yang enak dibaca. */
export function formatJam(
  buka: string | null | undefined,
  tutup: string | null | undefined,
): string | null {
  const start = buka ? buka.slice(0, 5) : null;
  const end = tutup ? tutup.slice(0, 5) : null;

  if (start && end) {
    return `${start} - ${end}`;
  }

  return start ?? end;
}

export function formatTanggal(iso: string | null | undefined): string {
  if (!iso) {
    return "";
  }

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return "";
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(date);
}

export function formatTanggalJam(iso: string | null | undefined): string {
  if (!iso) {
    return "";
  }

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return "";
  }

  return new Intl.DateTimeFormat("id-ID", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}

/** Waktu relatif sederhana, mis. "2 jam lalu". */
export function waktuRelatif(iso: string | null | undefined): string {
  if (!iso) {
    return "";
  }

  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return "";
  }

  const seconds = Math.round((Date.now() - date.getTime()) / 1000);

  if (seconds < 60) {
    return "baru saja";
  }

  const minutes = Math.round(seconds / 60);
  if (minutes < 60) {
    return `${minutes} menit lalu`;
  }

  const hours = Math.round(minutes / 60);
  if (hours < 24) {
    return `${hours} jam lalu`;
  }

  const days = Math.round(hours / 24);
  if (days < 30) {
    return `${days} hari lalu`;
  }

  return formatTanggal(iso);
}
