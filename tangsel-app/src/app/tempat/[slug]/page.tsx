import { notFound } from "next/navigation";
import DetailDirektori, { type DetailField } from "@/components/direktori/DetailDirektori";
import { ApiError, getKulinerDetail, getTerdekat, type Tempat } from "@/lib/api";
import { getCurrentUser } from "@/lib/auth";
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

  const [tempat, terdekat, user] = await Promise.all([
    loadTempat(slug),
    getTerdekat("kuliner", slug)
      .then((hasil) => hasil.data)
      .catch(() => []),
    getCurrentUser(),
  ]);

  const fields: DetailField[] = [];
  if (tempat.rentang_harga) {
    fields.push({ label: "Kisaran Harga", value: tempat.rentang_harga });
  }

  const foto = tempat.ulasan.daftar.flatMap((ulasan) => ulasan.foto ?? []);

  return (
    <DetailDirektori
      data={{
        fitur: "kuliner",
        slug: tempat.slug,
        nama: tempat.nama,
        jenis: tempat.jenis,
        jenisLabel: labelOf(LABEL_JENIS_TEMPAT, tempat.jenis),
        rating: ratingText(tempat.ulasan.rating ?? tempat.rating),
        jumlahRating: tempat.ulasan.jumlah || tempat.rating_kount,
        deskripsi: tempat.deskripsi,
        deskripsiSumber: tempat.deskripsi_sumber,
        alamat: tempat.alamat,
        kecamatan: tempat.kecamatan?.nama ?? null,
        jam: formatJam(tempat.jam_buka, tempat.jam_tutup),
        jamTeks: tempat.jam_buka_teks,
        fasilitas: tempat.fasilitas ?? [],
        menu: tempat.menu ?? [],
        telepon: tempat.telepon,
        website: tempat.website,
        latitude: tempat.latitude,
        longitude: tempat.longitude,
        gambar: tempat.gambar,
        foto,
        sumber: tempat.sumber,
        fields,
        listHref: "/kuliner",
        listLabel: "Kuliner",
        accent: "var(--accent)",
        atribusi: tempat.atribusi,
        ulasan: tempat.ulasan,
        terdekat,
        sudahMasuk: user !== null,
      }}
    />
  );
}
