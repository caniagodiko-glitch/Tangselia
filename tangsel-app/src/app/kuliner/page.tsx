import { Coffee } from "lucide-react";
import DirektoriListView, { type DirektoriPill } from "@/components/direktori/DirektoriListView";
import { getKuliner, getKulinerMeta } from "@/lib/api";
import { LABEL_JENIS_TEMPAT, labelOf, ratingText } from "@/lib/format";

type SearchParams = Record<string, string | string[] | undefined>;

const str = (value: string | string[] | undefined): string | undefined => {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw?.trim() || undefined;
};

export default async function KulinerPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const sp = await searchParams;
  const current = {
    jenis: str(sp.jenis),
    kecamatan: str(sp.kecamatan),
    q: str(sp.q),
  };

  const [list, meta] = await Promise.all([
    getKuliner({ ...current, per_page: 30 }),
    getKulinerMeta(),
  ]);

  const total = meta.data.jenis.reduce((sum, jenis) => sum + jenis.jumlah, 0);

  const buildHref = (patch: Partial<typeof current>): string => {
    const merged = { ...current, ...patch };
    const params = new URLSearchParams();
    if (merged.jenis) params.set("jenis", merged.jenis);
    if (merged.kecamatan) params.set("kecamatan", merged.kecamatan);
    if (merged.q) params.set("q", merged.q);
    const qs = params.toString();
    return qs ? `/kuliner?${qs}` : "/kuliner";
  };

  const pills: DirektoriPill[] = [
    { label: "Semua", jumlah: total, href: buildHref({ jenis: undefined }), active: !current.jenis },
    ...meta.data.jenis.map((jenis) => ({
      label: labelOf(LABEL_JENIS_TEMPAT, jenis.value),
      jumlah: jenis.jumlah,
      href: buildHref({ jenis: jenis.value }),
      active: current.jenis === jenis.value,
    })),
  ];

  const items = list.data.map((tempat) => ({
    id: tempat.id,
    nama: tempat.nama,
    href: `/tempat/${tempat.slug}`,
    jenisLabel: labelOf(LABEL_JENIS_TEMPAT, tempat.jenis),
    lokasi: tempat.kecamatan?.nama ?? tempat.alamat,
    rating: ratingText(tempat.rating),
    gambar: tempat.gambar,
  }));

  return (
    <DirektoriListView
      eyebrow="JELAJAH RASA"
      title="Kopi & Kuliner Tangsel."
      subtitle="Temukan kafe, warkop, dan restoran pilihan di seluruh Tangerang Selatan — dari hidden gem sampai tempat favorit warga."
      accent="var(--accent)"
      icon={<Coffee size={44} />}
      searchAction="/kuliner"
      searchPlaceholder="Cari kafe, warkop, atau restoran..."
      searchValue={current.q ?? ""}
      hiddenParams={{ jenis: current.jenis, kecamatan: current.kecamatan }}
      pills={pills}
      items={items}
      total={total}
      emptyText="Belum ada tempat yang cocok dengan pencarianmu. Coba kata kunci atau kategori lain."
    />
  );
}
