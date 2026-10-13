import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getKulinerDetail, type Tempat } from "@/lib/api";
import { LABEL_JENIS_TEMPAT, formatJam, labelOf, ratingText } from "@/lib/format";

async function loadTempat(slug: string): Promise<Tempat> {
  try {
    return (await getKulinerDetail(slug)).data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }
    throw error;
  }
}

export default async function TempatDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const tempat = await loadTempat(slug);

  const fields: DetailField[] = [];
  if (tempat.rentang_harga) {
    fields.push({ label: "Kisaran Harga", value: tempat.rentang_harga });
  }

  return (
    <DetailDirektori
      data={{
        nama: tempat.nama,
        jenisLabel: labelOf(LABEL_JENIS_TEMPAT, tempat.jenis),
        rating: ratingText(tempat.rating),
        jumlahRating: tempat.rating_kount,
        deskripsi: tempat.deskripsi,
        alamat: tempat.alamat,
        kecamatan: tempat.kecamatan?.nama ?? null,
        jam: formatJam(tempat.jam_buka, tempat.jam_tutup),
        jamTeks: tempat.jam_buka_teks,
        fasilitas: tempat.fasilitas ?? [],
        telepon: null,
        latitude: tempat.latitude,
        longitude: tempat.longitude,
        gambar: tempat.gambar,
        sumber: tempat.sumber,
        googlePlaceId: tempat.google_place_id,
        fields,
        listHref: "/kuliner",
        listLabel: "Kuliner",
        accent: "var(--accent)",
      }}
    />
  );
}
