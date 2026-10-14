import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getOlahragaDetail, getTerdekat, type FasilitasOlahraga } from "@/lib/api";
import { getCurrentUser } from "@/lib/auth";
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

  const [fasilitas, terdekat, user] = await Promise.all([
    loadFasilitas(slug),
    getTerdekat("olahraga", slug)
      .then((hasil) => hasil.data)
      .catch(() => []),
    getCurrentUser(),
  ]);

  const fields: DetailField[] = [];
  if (fasilitas.kapasitas) {
    fields.push({ label: "Kapasitas", value: `${fasilitas.kapasitas} orang` });
  }
  if (fasilitas.tarif_sewa) {
    fields.push({ label: "Tarif Sewa", value: fasilitas.tarif_sewa });
  }

  const foto = fasilitas.ulasan.daftar.flatMap((ulasan) => ulasan.foto ?? []);

  return (
    <DetailDirektori
      data={{
        fitur: "olahraga",
        slug: fasilitas.slug,
        nama: fasilitas.nama,
        jenis: fasilitas.jenis,
        jenisLabel: labelOf(LABEL_JENIS_OLAHRAGA, fasilitas.jenis),
        rating: ratingText(fasilitas.ulasan.rating ?? fasilitas.rating),
        jumlahRating: fasilitas.ulasan.jumlah || null,
        deskripsi: fasilitas.deskripsi,
        deskripsiSumber: fasilitas.deskripsi_sumber,
        alamat: fasilitas.alamat,
        kecamatan: fasilitas.kecamatan?.nama ?? null,
        jam: null,
        jamTeks: fasilitas.jam_buka_teks,
        fasilitas: fasilitas.fasilitas ?? [],
        menu: [],
        telepon: fasilitas.telepon,
        website: fasilitas.website,
        latitude: fasilitas.latitude,
        longitude: fasilitas.longitude,
        gambar: fasilitas.gambar,
        foto,
        sumber: fasilitas.sumber,
        fields,
        listHref: "/olahraga",
        listLabel: "Olahraga",
        accent: "var(--primary)",
        atribusi: fasilitas.atribusi,
        ulasan: fasilitas.ulasan,
        terdekat,
        sudahMasuk: user !== null,
      }}
    />
  );
}
