import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getKesehatanDetail, getTerdekat, type LayananKesehatan } from "@/lib/api";
import { getCurrentUser } from "@/lib/auth";
import { LABEL_JENIS_KESEHATAN, formatJam, labelOf, ratingText } from "@/lib/format";

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

  const [layanan, terdekat, user] = await Promise.all([
    loadLayanan(slug),
    getTerdekat("kesehatan", slug)
      .then((hasil) => hasil.data)
      .catch(() => []),
    getCurrentUser(),
  ]);

  const fields: DetailField[] = [
    {
      label: "Layanan 24 Jam",
      value: layanan.layanan_24_jam ? "Tersedia" : "Tidak / belum terdata",
    },
  ];

  const foto = layanan.ulasan.daftar.flatMap((ulasan) => ulasan.foto ?? []);

  return (
    <DetailDirektori
      data={{
        fitur: "kesehatan",
        slug: layanan.slug,
        nama: layanan.nama,
        jenis: layanan.jenis,
        jenisLabel: labelOf(LABEL_JENIS_KESEHATAN, layanan.jenis),
        rating: ratingText(layanan.ulasan.rating),
        jumlahRating: layanan.ulasan.jumlah || null,
        deskripsi: layanan.deskripsi,
        deskripsiSumber: layanan.deskripsi_sumber,
        alamat: layanan.alamat,
        kecamatan: layanan.kecamatan?.nama ?? null,
        jam: formatJam(layanan.jam_buka, layanan.jam_tutup),
        jamTeks: layanan.jam_buka_teks,
        fasilitas: layanan.fasilitas ?? [],
        menu: [],
        telepon: layanan.telepon,
        website: layanan.website,
        latitude: layanan.latitude,
        longitude: layanan.longitude,
        gambar: null,
        foto,
        sumber: layanan.sumber,
        fields,
        listHref: "/kesehatan",
        listLabel: "Kesehatan",
        accent: "#0ea5e9",
        atribusi: layanan.atribusi,
        ulasan: layanan.ulasan,
        terdekat,
        sudahMasuk: user !== null,
      }}
    />
  );
}
