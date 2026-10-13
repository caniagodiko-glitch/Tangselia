import { Activity } from "lucide-react";
import DirektoriListView, { type DirektoriPill } from "@/components/direktori/DirektoriListView";
import { getOlahraga, getOlahragaMeta } from "@/lib/api";
import { LABEL_JENIS_OLAHRAGA, labelOf, ratingText } from "@/lib/format";

type SearchParams = Record<string, string | string[] | undefined>;

const str = (value: string | string[] | undefined): string | undefined => {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw?.trim() || undefined;
};

export default async function OlahragaPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const sp = await searchParams;
  const current = {
    jenis: str(sp.jenis),
    kecamatan: str(sp.kecamatan),
    q: str(sp.q),
  };

  const [list, meta] = await Promise.all([
    getOlahraga({ ...current, per_page: 30 }),
    getOlahragaMeta(),
  ]);

  const total = meta.data.jenis.reduce((sum, jenis) => sum + jenis.jumlah, 0);

  const buildHref = (patch: Partial<typeof current>): string => {
    const merged = { ...current, ...patch };
    const params = new URLSearchParams();
    if (merged.jenis) params.set("jenis", merged.jenis);
    if (merged.kecamatan) params.set("kecamatan", merged.kecamatan);
    if (merged.q) params.set("q", merged.q);
    const qs = params.toString();
    return qs ? `/olahraga?${qs}` : "/olahraga";
  };

  const pills: DirektoriPill[] = [
    { label: "Semua", jumlah: total, href: buildHref({ jenis: undefined }), active: !current.jenis },
    ...meta.data.jenis.map((jenis) => ({
      label: labelOf(LABEL_JENIS_OLAHRAGA, jenis.value),
      jumlah: jenis.jumlah,
      href: buildHref({ jenis: jenis.value }),
      active: current.jenis === jenis.value,
    })),
  ];

  const items = list.data.map((fasilitas) => ({
    id: fasilitas.id,
    nama: fasilitas.nama,
    href: `/olahraga/${fasilitas.slug}`,
    jenisLabel: labelOf(LABEL_JENIS_OLAHRAGA, fasilitas.jenis),
    lokasi: fasilitas.kecamatan?.nama ?? fasilitas.alamat,
    rating: ratingText(fasilitas.rating),
    gambar: fasilitas.gambar,
  }));

  return (
    <DirektoriListView
      eyebrow="TANGSEL AKTIF"
      title="Fasilitas Olahraga Tangsel."
      subtitle="Dari GOR bulutangkis, lapangan futsal, kolam renang, sampai gym di seluruh Tangerang Selatan."
      accent="var(--primary)"
      icon={<Activity size={44} />}
      searchAction="/olahraga"
      searchPlaceholder="Cari GOR, lapangan, kolam renang..."
      searchValue={current.q ?? ""}
      hiddenParams={{ jenis: current.jenis, kecamatan: current.kecamatan }}
      pills={pills}
      items={items}
      total={total}
      emptyText="Belum ada fasilitas olahraga yang cocok. Coba kategori atau kata kunci lain."
    />
  );
}
