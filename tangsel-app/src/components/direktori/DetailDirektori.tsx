import type { ReactNode } from "react";
import Link from "next/link";
import {
  ArrowLeft,
  Clock,
  Compass,
  Database,
  ExternalLink,
  Globe,
  Images,
  Info,
  Map as MapIcon,
  MapPin,
  Navigation,
  Phone,
  Star,
  Utensils,
} from "lucide-react";
import {
  mediaUrl,
  type Atribusi,
  type DirektoriFitur,
  type MenuItem,
  type RingkasanUlasan,
  type TempatTerdekat,
} from "@/lib/api";
import { FASILITAS_UMUM, LABEL_JENIS_KESEHATAN, LABEL_JENIS_OLAHRAGA, LABEL_JENIS_TEMPAT, labelOf } from "@/lib/format";
import UlasanForm from "./UlasanForm";

export type DetailField = { label: string; value: string };

export type DetailDirektoriData = {
  fitur: DirektoriFitur;
  slug: string;
  nama: string;
  jenis: string;
  jenisLabel: string;
  rating: string;
  jumlahRating?: number | null;
  deskripsi: string | null;
  deskripsiSumber: string | null;
  alamat: string;
  kecamatan: string | null;
  jam: string | null;
  jamTeks: string | null;
  fasilitas: string[];
  menu: MenuItem[];
  telepon: string | null;
  website: string | null;
  latitude: number | string;
  longitude: number | string;
  gambar: string | null;
  foto: string[];
  sumber: string;
  fields: DetailField[];
  listHref: string;
  listLabel: string;
  accent: string;
  atribusi: Atribusi[];
  ulasan: RingkasanUlasan;
  terdekat: TempatTerdekat[];
  sudahMasuk: boolean;
};

const LABEL_SUMBER_DESKRIPSI: Record<string, string> = {
  osm: "Rangkuman data OpenStreetMap",
  website: "Situs resmi tempat",
  wikipedia: "Wikipedia (CC BY-SA)",
  ai: "Rangkuman otomatis dari data publik",
  editor: "Ditulis tim TANGSEL LIA",
  gmaps: "Rangkuman dari data Google Maps",
};

const LABEL_BY_FITUR: Record<DirektoriFitur, Record<string, string>> = {
  kuliner: LABEL_JENIS_TEMPAT,
  olahraga: LABEL_JENIS_OLAHRAGA,
  kesehatan: LABEL_JENIS_KESEHATAN,
};

export default function DetailDirektori({ data }: { data: DetailDirektoriData }) {
  const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${data.latitude},${data.longitude}`;
  const osmUrl = `https://www.openstreetmap.org/?mlat=${data.latitude}&mlon=${data.longitude}#map=18/${data.latitude}/${data.longitude}`;

  const lat = Number(data.latitude);
  const lon = Number(data.longitude);
  const pad = 0.006;
  const embedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${lon - pad}%2C${lat - pad}%2C${lon + pad}%2C${lat + pad}&layer=mapnik&marker=${lat}%2C${lon}`;

  const galeri = Array.from(
    new Set([mediaUrl(data.gambar), ...data.foto.map((item) => mediaUrl(item))].filter((item): item is string => Boolean(item))),
  );

  // Foto tambahan di luar foto header, agar tidak tampil dobel.
  const gambarUtama = mediaUrl(data.gambar);
  const galeriTambahan = galeri.filter((url) => url !== gambarUtama);

  const labelJenis = LABEL_BY_FITUR[data.fitur];
  const sumberDeskripsi = data.deskripsiSumber
    ? (LABEL_SUMBER_DESKRIPSI[data.deskripsiSumber] ?? data.deskripsiSumber)
    : null;

  // Fasilitas cadangan (umum per jenis) bila data sumber kosong.
  const fasilitasUmum = data.fasilitas.length === 0 ? (FASILITAS_UMUM[data.jenis] ?? []) : [];
  const fasilitasTampil = data.fasilitas.length > 0 ? data.fasilitas : fasilitasUmum;

  return (
    <div style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <div
        style={{
          position: "relative",
          height: "320px",
          background: `linear-gradient(135deg, ${data.accent}, var(--primary-dark))`,
        }}
      >
        {data.gambar && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={mediaUrl(data.gambar) ?? undefined}
            alt={data.nama}
            style={{ width: "100%", height: "100%", objectFit: "cover" }}
          />
        )}
        <div
          style={{
            position: "absolute",
            inset: 0,
            background:
              "linear-gradient(to bottom, rgba(0,0,0,0.35), rgba(0,0,0,0.05) 45%, rgba(255,255,255,0) 75%)",
          }}
        />
        <div className="container" style={{ position: "absolute", top: 0, left: 0, right: 0, paddingTop: "1.25rem" }}>
          <Link
            href={data.listHref}
            style={{
              display: "inline-flex",
              alignItems: "center",
              gap: "0.5rem",
              background: "rgba(255,255,255,0.92)",
              color: "var(--text-main)",
              padding: "0.5rem 0.9rem",
              borderRadius: "999px",
              fontWeight: 600,
              fontSize: "0.85rem",
            }}
          >
            <ArrowLeft size={16} /> {data.listLabel}
          </Link>
        </div>
      </div>

      <div className="container" style={{ maxWidth: "900px", marginTop: "-3rem", position: "relative", zIndex: 5 }}>
        <div
          style={{
            background: "#fff",
            borderRadius: "24px",
            border: "1px solid var(--border)",
            boxShadow: "0 20px 40px rgba(0,0,0,0.06)",
            padding: "var(--spacing-lg)",
          }}
        >
          <div
            style={{
              display: "flex",
              justifyContent: "space-between",
              gap: "1rem",
              flexWrap: "wrap",
              alignItems: "center",
              marginBottom: "0.75rem",
            }}
          >
            <span
              style={{
                background: "#f0f7f6",
                color: "var(--primary)",
                padding: "0.35rem 0.9rem",
                borderRadius: "999px",
                fontSize: "0.75rem",
                fontWeight: 700,
                textTransform: "uppercase",
                letterSpacing: "0.05em",
              }}
            >
              {data.jenisLabel}
            </span>
            {data.rating !== "-" && (
              <span style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem", fontWeight: 700 }}>
                <Star size={18} fill="currentColor" color="#FBBF24" /> {data.rating}
                {typeof data.jumlahRating === "number" && data.jumlahRating > 0 && (
                  <span style={{ color: "var(--text-light)", fontWeight: 500, fontSize: "0.85rem" }}>
                    ({data.jumlahRating})
                  </span>
                )}
              </span>
            )}
          </div>

          <h1 style={{ fontSize: "clamp(1.75rem, 4vw, 2.5rem)", marginBottom: "0.75rem" }}>{data.nama}</h1>

          {data.deskripsi ? (
            <>
              <p style={{ color: "var(--text-muted)", marginBottom: "0.5rem", lineHeight: 1.7 }}>{data.deskripsi}</p>
              {sumberDeskripsi && (
                <p style={{ color: "var(--text-light)", fontSize: "0.78rem", marginBottom: "1.5rem", fontStyle: "italic" }}>
                  {sumberDeskripsi}
                </p>
              )}
            </>
          ) : (
            <BelumTerdata teks="Deskripsi belum tersedia untuk tempat ini." />
          )}

          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))",
              gap: "1rem",
              margin: "1.5rem 0",
            }}
          >
            <InfoRow
              icon={<MapPin size={18} />}
              label="Lokasi"
              value={[data.alamat, data.kecamatan].filter(Boolean).join(", ") || "Tangsel"}
            />
            {data.jam ? (
              <InfoRow icon={<Clock size={18} />} label="Jam Operasional" value={data.jam} />
            ) : data.jamTeks ? (
              <InfoRow icon={<Clock size={18} />} label="Jam Operasional" value={data.jamTeks} />
            ) : null}
            {data.telepon ? (
              <InfoRow icon={<Phone size={18} />} label="Telepon" value={data.telepon} />
            ) : null}
            {data.website ? (
              <InfoRow icon={<Globe size={18} />} label="Situs Resmi" value={data.website} href={data.website} />
            ) : null}
            {data.fields.map((field) => (
              <InfoRow key={field.label} icon={<Database size={18} />} label={field.label} value={field.value} />
            ))}
          </div>

          <div style={{ display: "flex", gap: "0.75rem", flexWrap: "wrap" }}>
            <a
              href={mapsUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-primary"
              style={{ gap: "0.5rem" }}
            >
              <Navigation size={18} /> Rute Perjalanan
            </a>
            <a
              href={osmUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-outline"
              style={{ gap: "0.5rem" }}
            >
              <MapIcon size={18} /> Lihat di OpenStreetMap
            </a>
            {data.website && (
              <a
                href={data.website}
                target="_blank"
                rel="noopener noreferrer"
                className="btn btn-outline"
                style={{ gap: "0.5rem" }}
              >
                <Globe size={18} /> Situs Web
              </a>
            )}
            {data.telepon && (
              <a href={`tel:${data.telepon}`} className="btn btn-outline" style={{ gap: "0.5rem" }}>
                <Phone size={18} /> Telepon
              </a>
            )}
          </div>

          <Section title="Fasilitas & Layanan">
            {fasilitasTampil.length > 0 ? (
              <>
                <div style={{ display: "flex", flexWrap: "wrap", gap: "0.5rem" }}>
                  {fasilitasTampil.map((item, index) => (
                    <span
                      key={`${item}-${index}`}
                      style={{
                        background: "var(--surface-hover)",
                        border: "1px solid var(--border)",
                        padding: "0.4rem 0.8rem",
                        borderRadius: "8px",
                        fontSize: "0.8rem",
                        color: "var(--text-muted)",
                      }}
                    >
                      {item}
                    </span>
                  ))}
                </div>
                {data.fasilitas.length === 0 && (
                  <p style={{ color: "var(--text-light)", fontSize: "0.75rem", marginTop: "0.6rem" }}>
                    Fasilitas umum untuk jenis ini (data spesifik tempat belum tersedia).
                  </p>
                )}
              </>
            ) : (
              <BelumTerdata teks="Fasilitas belum terdata. Bantu lengkapi lewat ulasan warga." />
            )}
          </Section>

          <Section title="Menu" icon={<Utensils size={20} />}>
            {data.menu.length > 0 ? (
              <ul style={{ listStyle: "none", display: "grid", gap: "0.5rem" }}>
                {data.menu.map((item, index) => (
                  <li
                    key={`${item.nama}-${index}`}
                    style={{
                      display: "flex",
                      justifyContent: "space-between",
                      gap: "1rem",
                      borderBottom: "1px dashed var(--border)",
                      paddingBottom: "0.5rem",
                    }}
                  >
                    <span style={{ color: "var(--text-main)" }}>{item.nama}</span>
                    {item.harga && (
                      <span style={{ color: "var(--text-muted)", fontWeight: 600, whiteSpace: "nowrap" }}>
                        {item.harga}
                      </span>
                    )}
                  </li>
                ))}
              </ul>
            ) : (
              <BelumTerdata teks="Menu belum terdata. Daftar menu hanya ditampilkan bila ada sumber resminya." />
            )}
          </Section>

          {galeriTambahan.length > 0 && (
            <Section title="Galeri Foto" icon={<Images size={20} />}>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(150px, 1fr))", gap: "0.75rem" }}>
                {galeriTambahan.map((url) => (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    key={url}
                    src={url}
                    alt={data.nama}
                    style={{ width: "100%", height: "130px", objectFit: "cover", borderRadius: "12px", border: "1px solid var(--border)" }}
                  />
                ))}
              </div>
            </Section>
          )}

          <Section title="Peta Lokasi" icon={<MapIcon size={20} />}>
            <div style={{ borderRadius: "16px", overflow: "hidden", border: "1px solid var(--border)" }}>
              <iframe
                title={`Peta ${data.nama}`}
                src={embedUrl}
                loading="lazy"
                style={{ width: "100%", height: "320px", border: 0, display: "block" }}
              />
            </div>
            <p style={{ color: "var(--text-light)", fontSize: "0.75rem", marginTop: "0.5rem" }}>
              Peta © kontributor{" "}
              <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">
                OpenStreetMap
              </a>{" "}
              (ODbL).
            </p>
          </Section>

          {data.terdekat.length > 0 && (
            <Section title="Tempat Terdekat" icon={<Compass size={20} />}>
              <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(220px, 1fr))", gap: "0.75rem" }}>
                {data.terdekat.map((item) => (
                  <Link
                    key={item.id}
                    href={`${data.listHref}/${item.slug}`}
                    style={{
                      border: "1px solid var(--border)",
                      borderRadius: "12px",
                      padding: "0.9rem",
                      display: "block",
                      background: "#fff",
                    }}
                  >
                    <div style={{ fontWeight: 700, marginBottom: "0.25rem", color: "var(--text-main)" }}>{item.nama}</div>
                    <div style={{ color: "var(--text-light)", fontSize: "0.78rem", marginBottom: "0.4rem" }}>
                      {labelOf(labelJenis, item.jenis)}
                    </div>
                    <div style={{ display: "flex", justifyContent: "space-between", color: "var(--text-muted)", fontSize: "0.8rem" }}>
                      <span>{item.kecamatan ?? "Tangsel"}</span>
                      <span style={{ fontWeight: 600 }}>{formatJarak(item.jarak_m)}</span>
                    </div>
                  </Link>
                ))}
              </div>
            </Section>
          )}

          <Section title={`Ulasan Warga${data.ulasan.jumlah > 0 ? ` (${data.ulasan.jumlah})` : ""}`} icon={<Star size={20} />}>
            {data.ulasan.jumlah > 0 && data.ulasan.rating !== null && (
              <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "1rem" }}>
                <span style={{ fontSize: "1.75rem", fontWeight: 800, color: "var(--text-main)" }}>
                  {data.ulasan.rating.toFixed(1)}
                </span>
                <span style={{ display: "inline-flex", gap: "0.1rem" }}>
                  {[1, 2, 3, 4, 5].map((bintang) => (
                    <Star
                      key={bintang}
                      size={16}
                      fill={bintang <= Math.round(data.ulasan.rating ?? 0) ? "currentColor" : "none"}
                      color="#FBBF24"
                    />
                  ))}
                </span>
                <span style={{ color: "var(--text-light)", fontSize: "0.85rem" }}>dari {data.ulasan.jumlah} ulasan</span>
              </div>
            )}

            {data.ulasan.daftar.length > 0 ? (
              <div style={{ display: "flex", flexDirection: "column", gap: "1rem", marginBottom: "1.5rem" }}>
                {data.ulasan.daftar.map((item) => (
                  <div key={item.id} style={{ borderBottom: "1px solid var(--border)", paddingBottom: "1rem" }}>
                    <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "0.35rem" }}>
                      <span style={{ display: "inline-flex", gap: "0.1rem" }}>
                        {[1, 2, 3, 4, 5].map((bintang) => (
                          <Star
                            key={bintang}
                            size={13}
                            fill={bintang <= item.rating ? "currentColor" : "none"}
                            color="#FBBF24"
                          />
                        ))}
                      </span>
                      <span style={{ fontWeight: 600, fontSize: "0.9rem" }}>{item.penulis ?? "Warga"}</span>
                    </div>
                    <p style={{ color: "var(--text-muted)", lineHeight: 1.6, marginBottom: item.foto && item.foto.length > 0 ? "0.6rem" : 0 }}>
                      {item.komentar}
                    </p>
                    {item.foto && item.foto.length > 0 && (
                      <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
                        {item.foto.map((path) => {
                          const url = mediaUrl(path);
                          return url ? (
                            // eslint-disable-next-line @next/next/no-img-element
                            <img
                              key={path}
                              src={url}
                              alt={`Foto ulasan ${item.penulis ?? "warga"}`}
                              style={{ width: "96px", height: "96px", objectFit: "cover", borderRadius: "10px", border: "1px solid var(--border)" }}
                            />
                          ) : null;
                        })}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            ) : (
              <p style={{ color: "var(--text-muted)", marginBottom: "1.5rem" }}>
                Belum ada ulasan. Jadi yang pertama berbagi pengalaman!
              </p>
            )}

            <UlasanForm fitur={data.fitur} slug={data.slug} sudahMasuk={data.sudahMasuk} />
          </Section>

          <Section title="Sumber & Atribusi" icon={<Info size={20} />}>
            <ul style={{ listStyle: "none", display: "flex", flexDirection: "column", gap: "0.6rem" }}>
              {data.atribusi.map((entri, index) => (
                <li key={`${entri.sumber}-${index}`} style={{ color: "var(--text-muted)", fontSize: "0.85rem", lineHeight: 1.6 }}>
                  <strong style={{ color: "var(--text-main)" }}>{entri.sumber}</strong>
                  {entri.peran ? ` — ${entri.peran}` : ""}
                  {entri.lisensi ? ` · ${entri.lisensi}` : ""}
                  {entri.tautan && (
                    <>
                      {" · "}
                      <a href={entri.tautan} target="_blank" rel="noopener noreferrer" style={{ display: "inline-flex", alignItems: "center", gap: "0.2rem" }}>
                        tautan <ExternalLink size={12} />
                      </a>
                    </>
                  )}
                </li>
              ))}
            </ul>
          </Section>
        </div>
      </div>
    </div>
  );
}

function Section({ title, icon, children }: { title: string; icon?: ReactNode; children: ReactNode }) {
  return (
    <section style={{ marginTop: "2rem", paddingTop: "1.5rem", borderTop: "1px solid var(--border)" }}>
      <h3 style={{ marginBottom: "1rem", fontSize: "1.25rem", display: "flex", alignItems: "center", gap: "0.5rem" }}>
        {icon && <span style={{ color: "var(--primary)", display: "inline-flex" }}>{icon}</span>}
        {title}
      </h3>
      {children}
    </section>
  );
}

function BelumTerdata({ teks }: { teks: string }) {
  return (
    <div
      style={{
        display: "flex",
        alignItems: "center",
        gap: "0.5rem",
        border: "1px dashed var(--border)",
        borderRadius: "12px",
        padding: "0.85rem 1rem",
        color: "var(--text-light)",
        fontSize: "0.88rem",
        background: "var(--surface-hover)",
      }}
    >
      <Info size={16} /> {teks}
    </div>
  );
}

function InfoRow({
  icon,
  label,
  value,
  href,
}: {
  icon: ReactNode;
  label: string;
  value: string;
  href?: string;
}) {
  return (
    <div style={{ display: "flex", gap: "0.75rem", alignItems: "flex-start" }}>
      <div
        style={{
          width: "42px",
          height: "42px",
          borderRadius: "12px",
          background: "var(--surface-hover)",
          color: "var(--primary)",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          flexShrink: 0,
        }}
      >
        {icon}
      </div>
      <div style={{ minWidth: 0 }}>
        <div
          style={{
            fontSize: "0.75rem",
            fontWeight: 700,
            color: "var(--text-light)",
            textTransform: "uppercase",
            letterSpacing: "0.05em",
            marginBottom: "0.15rem",
          }}
        >
          {label}
        </div>
        {href ? (
          <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            style={{ color: "var(--primary)", fontSize: "0.95rem", wordBreak: "break-all" }}
          >
            {value}
          </a>
        ) : (
          <div style={{ color: "var(--text-main)", fontSize: "0.95rem" }}>{value}</div>
        )}
      </div>
    </div>
  );
}

function formatJarak(meter: number): string {
  if (meter < 1000) {
    return `${meter} m`;
  }
  return `${(meter / 1000).toFixed(1)} km`;
}
