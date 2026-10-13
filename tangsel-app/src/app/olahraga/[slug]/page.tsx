import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getOlahragaDetail, type FasilitasOlahraga } from "@/lib/api";
import { LABEL_JENIS_OLAHRAGA, labelOf, ratingText } from "@/lib/format";

async function loadFasilitas(slug: string): Promise<FasilitasOlahraga> {
  try {
    return (await getOlahragaDetail(slug)).data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }
    throw error;
  }
}

export default async function OlahragaDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const fasilitas = await loadFasilitas(slug);

  const fields: DetailField[] = [];
  if (fasilitas.kapasitas) {
    fields.push({ label: "Kapasitas", value: `${fasilitas.kapasitas} orang` });
  }
  if (fasilitas.tarif_sewa) {
    fields.push({ label: "Tarif Sewa", value: fasilitas.tarif_sewa });
  }

  return (
    <DetailDirektori
      data={{
        nama: fasilitas.nama,
        jenisLabel: labelOf(LABEL_JENIS_OLAHRAGA, fasilitas.jenis),
        rating: ratingText(fasilitas.rating),
        jumlahRating: null,
        deskripsi: fasilitas.deskripsi,
        alamat: fasilitas.alamat,
        kecamatan: fasilitas.kecamatan?.nama ?? null,
        jam: null,
        jamTeks: fasilitas.jam_buka_teks,
        fasilitas: fasilitas.fasilitas ?? [],
        telepon: null,
        latitude: fasilitas.latitude,
        longitude: fasilitas.longitude,
        gambar: fasilitas.gambar,
        sumber: fasilitas.sumber,
        googlePlaceId: fasilitas.google_place_id,
        fields,
        listHref: "/olahraga",
        listLabel: "Olahraga",
        accent: "var(--primary)",
      }}
    />
  );
}
