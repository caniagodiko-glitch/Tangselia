import type { ReactNode } from "react";
import { ArrowLeft, MapPin, Star, Clock, Navigation, Phone, Database } from "lucide-react";
import Link from "next/link";

export type DetailField = { label: string; value: string };

export type DetailDirektoriData = {
  nama: string;
  jenisLabel: string;
  rating: string;
  jumlahRating?: number | null;
  deskripsi: string | null;
  alamat: string;
  kecamatan: string | null;
  jam: string | null;
  jamTeks: string | null;
  fasilitas: string[];
  telepon: string | null;
  latitude: number | string;
  longitude: number | string;
  gambar: string | null;
  sumber: string;
  googlePlaceId: string | null;
  fields: DetailField[];
  listHref: string;
  listLabel: string;
  accent: string;
};

export default function DetailDirektori({ data }: { data: DetailDirektoriData }) {
  const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${data.latitude},${data.longitude}`;

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
            src={data.gambar}
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

          {data.deskripsi && (
            <p style={{ color: "var(--text-muted)", marginBottom: "1.5rem", lineHeight: 1.7 }}>{data.deskripsi}</p>
          )}

          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))",
              gap: "1rem",
              marginBottom: "1.5rem",
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
            {data.fields.map((field) => (
              <InfoRow key={field.label} icon={<Database size={18} />} label={field.label} value={field.value} />
            ))}
          </div>

          {data.fasilitas.length > 0 && (
            <>
              <h3 style={{ marginBottom: "1rem", fontSize: "1.25rem" }}>Fasilitas & Layanan</h3>
              <div style={{ display: "flex", flexWrap: "wrap", gap: "0.5rem", marginBottom: "1.5rem" }}>
                {data.fasilitas.map((item, index) => (
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
            </>
          )}

          <div style={{ display: "flex", gap: "0.75rem", flexWrap: "wrap" }}>
            <a
              href={mapsUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-primary"
              style={{ gap: "0.5rem" }}
            >
              <Navigation size={18} /> Arahkan
            </a>
            {data.telepon && (
              <a href={`tel:${data.telepon}`} className="btn btn-outline" style={{ gap: "0.5rem" }}>
                <Phone size={18} /> Telepon
              </a>
            )}
          </div>

          <div
            style={{
              marginTop: "1.5rem",
              paddingTop: "1rem",
              borderTop: "1px solid var(--border)",
              display: "flex",
              alignItems: "center",
              gap: "0.5rem",
              color: "var(--text-light)",
              fontSize: "0.8rem",
            }}
          >
            <Database size={14} />
            {data.sumber === "osm"
              ? "Data lokasi bersumber dari OpenStreetMap (ODbL)."
              : "Data dikelola oleh pengelola TANGSEL LIA."}
          </div>
        </div>
      </div>
    </div>
  );
}

function InfoRow({ icon, label, value }: { icon: ReactNode; label: string; value: string }) {
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
      <div>
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
        <div style={{ color: "var(--text-main)", fontSize: "0.95rem" }}>{value}</div>
      </div>
    </div>
  );
}
