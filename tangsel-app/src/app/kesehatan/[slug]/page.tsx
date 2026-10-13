import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getKesehatanDetail, type LayananKesehatan } from "@/lib/api";
import { LABEL_JENIS_KESEHATAN, formatJam, labelOf } from "@/lib/format";

async function loadLayanan(slug: string): Promise<LayananKesehatan> {
  try {
    return (await getKesehatanDetail(slug)).data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }
    throw error;
  }
}

export default async function KesehatanDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const layanan = await loadLayanan(slug);

  const fields: DetailField[] = [];
  fields.push({
    label: "Layanan 24 Jam",
    value: layanan.layanan_24_jam ? "Tersedia" : "Tidak / belum terdata",
  });

  return (
    <DetailDirektori
      data={{
        nama: layanan.nama,
        jenisLabel: labelOf(LABEL_JENIS_KESEHATAN, layanan.jenis),
        rating: "-",
        jumlahRating: null,
        deskripsi: layanan.deskripsi,
        alamat: layanan.alamat,
        kecamatan: layanan.kecamatan?.nama ?? null,
        jam: formatJam(layanan.jam_buka, layanan.jam_tutup),
        jamTeks: layanan.jam_buka_teks,
        fasilitas: layanan.fasilitas ?? [],
        telepon: layanan.telepon,
        latitude: layanan.latitude,
        longitude: layanan.longitude,
        gambar: null,
        sumber: layanan.sumber,
        googlePlaceId: layanan.google_place_id,
        fields,
        listHref: "/kesehatan",
        listLabel: "Kesehatan",
        accent: "#0ea5e9",
      }}
    />
  );
}
