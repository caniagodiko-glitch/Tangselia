import type { ReactNode } from "react";
import { Baby, Heart, Hospital, Pill, Stethoscope } from "lucide-react";
import DirektoriListView, { type DirektoriPill } from "@/components/direktori/DirektoriListView";
import { getKesehatan, getKesehatanMeta } from "@/lib/api";
import { LABEL_JENIS_KESEHATAN, labelOf, ratingText } from "@/lib/format";

type SearchParams = Record<string, string | string[] | undefined>;

const IKON_JENIS: Record<string, ReactNode> = {
  apotek: <Pill size={44} />,
  rumah_sakit: <Hospital size={44} />,
  puskesmas: <Heart size={44} />,
  klinik: <Stethoscope size={44} />,
  posyandu: <Baby size={44} />,
};

const WARNA_JENIS: Record<string, string> = {
  apotek: "#16a34a",
  rumah_sakit: "#dc2626",
  puskesmas: "#0ea5e9",
  klinik: "#7c3aed",
  posyandu: "#db2777",
};

const str = (value: string | string[] | undefined): string | undefined => {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw?.trim() || undefined;
};

export default async function KesehatanPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const sp = await searchParams;
  const current = {
    jenis: str(sp.jenis),
    kecamatan: str(sp.kecamatan),
    q: str(sp.q),
  };

  const [list, meta] = await Promise.all([
    getKesehatan({ ...current, per_page: 30 }),
    getKesehatanMeta(),
  ]);

  const total = meta.data.jenis.reduce((sum, jenis) => sum + jenis.jumlah, 0);

  const buildHref = (patch: Partial<typeof current>): string => {
    const merged = { ...current, ...patch };
    const params = new URLSearchParams();
    if (merged.jenis) params.set("jenis", merged.jenis);
    if (merged.kecamatan) params.set("kecamatan", merged.kecamatan);
    if (merged.q) params.set("q", merged.q);
    const qs = params.toString();
    return qs ? `/kesehatan?${qs}` : "/kesehatan";
  };

  const pills: DirektoriPill[] = [
    { label: "Semua", jumlah: total, href: buildHref({ jenis: undefined }), active: !current.jenis },
    ...meta.data.jenis.map((jenis) => ({
      label: labelOf(LABEL_JENIS_KESEHATAN, jenis.value),
      jumlah: jenis.jumlah,
      href: buildHref({ jenis: jenis.value }),
      active: current.jenis === jenis.value,
    })),
  ];

  const items = list.data.map((layanan) => ({
    id: layanan.id,
    nama: layanan.nama,
    href: `/kesehatan/${layanan.slug}`,
    jenisLabel: labelOf(LABEL_JENIS_KESEHATAN, layanan.jenis),
    lokasi: layanan.kecamatan?.nama ?? layanan.alamat,
    rating: ratingText(null),
    gambar: null,
    ikon: IKON_JENIS[layanan.jenis],
    accent: WARNA_JENIS[layanan.jenis],
  }));

  return (
    <DirektoriListView
      eyebrow="LAYANAN KESEHATAN"
      title="Akses kesehatan Tangsel."
      subtitle="Rumah sakit, puskesmas, klinik, sampai apotek di seluruh Tangerang Selatan — lengkap dengan jam operasional dan layanan 24 jam."
      accent="#0ea5e9"
      icon={<Heart size={44} />}
      searchAction="/kesehatan"
      searchPlaceholder="Cari rumah sakit, klinik, atau apotek..."
      searchValue={current.q ?? ""}
      hiddenParams={{ jenis: current.jenis, kecamatan: current.kecamatan }}
      pills={pills}
      items={items}
      total={total}
      emptyText="Belum ada layanan kesehatan yang cocok. Coba kategori atau kata kunci lain."
    />
  );
}
