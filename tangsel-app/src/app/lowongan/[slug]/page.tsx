import type { ReactNode } from "react";
import { notFound } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Briefcase, Building2, CalendarDays, ExternalLink, MapPin, Tag } from "lucide-react";
import { ApiError, getLowonganDetail, type Lowongan } from "@/lib/api";
import { LABEL_JENIS_LOWONGAN, formatTanggal } from "@/lib/format";

async function loadLowongan(slug: string): Promise<Lowongan> {
  try {
    return (await getLowonganDetail(slug)).data;
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }
    throw error;
  }
}

function InfoItem({ icon, children }: { icon: ReactNode; children: ReactNode }) {
  return (
    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.4rem", color: "var(--text-muted)", fontSize: "0.85rem" }}>
      {icon}
      {children}
    </span>
  );
}

function Bagian({ judul, isi }: { judul: string; isi: string }) {
  return (
    <section style={{ marginTop: "2rem" }}>
      <h2 style={{ fontFamily: "var(--font-display)", fontSize: "1.25rem", borderBottom: "2px solid #111", paddingBottom: "0.75rem", marginBottom: "1rem" }}>
        {judul}
      </h2>
      <p style={{ color: "var(--text-main)", lineHeight: 1.85, fontSize: "1rem", whiteSpace: "pre-line" }}>{isi}</p>
    </section>
  );
}

export default async function LowonganDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const lowongan = await loadLowongan(slug);

  return (
    <article style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <div className="container" style={{ maxWidth: "820px", paddingTop: "var(--spacing-lg)" }}>
        <Link
          href="/lowongan"
          style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem", color: "var(--text-muted)", fontWeight: 600, fontSize: "0.875rem" }}
        >
          <ArrowLeft size={16} /> Semua Lowongan
        </Link>

        <header style={{ margin: "2rem 0" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "0.75rem", marginBottom: "1rem", flexWrap: "wrap" }}>
            <span
              style={{
                fontSize: "0.7rem",
                fontWeight: 700,
                textTransform: "uppercase",
                letterSpacing: "0.08em",
                color: "var(--primary)",
                background: "#e6f4f1",
                padding: "0.2rem 0.7rem",
                borderRadius: "20px",
              }}
            >
              {LABEL_JENIS_LOWONGAN[lowongan.jenis] ?? lowongan.jenis}
            </span>
            {lowongan.kategori && (
              <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem", color: "var(--text-muted)", fontSize: "0.85rem" }}>
                <Tag size={13} /> {lowongan.kategori.nama}
              </span>
            )}
          </div>

          <h1 style={{ fontFamily: "var(--font-display)", fontSize: "clamp(2rem, 4vw, 3rem)", lineHeight: 1.15, color: "#111", marginBottom: "1.25rem" }}>
            {lowongan.posisi ?? lowongan.judul}
          </h1>

          {lowongan.posisi && (
            <p style={{ fontSize: "1.1rem", color: "var(--text-muted)", marginBottom: "1.25rem" }}>{lowongan.judul}</p>
          )}

          <div style={{ display: "flex", alignItems: "center", gap: "1.25rem", flexWrap: "wrap", paddingBottom: "1.5rem", borderBottom: "1px solid var(--border)" }}>
            {lowongan.perusahaan && (
              <InfoItem icon={<Building2 size={15} />}>{lowongan.perusahaan}</InfoItem>
            )}
            {lowongan.kecamatan && <InfoItem icon={<MapPin size={15} />}>{lowongan.kecamatan.nama}</InfoItem>}
            {lowongan.published_at && (
              <InfoItem icon={<CalendarDays size={15} />}>{formatTanggal(lowongan.published_at)}</InfoItem>
            )}
            <InfoItem icon={<Briefcase size={15} />}>{lowongan.kode}</InfoItem>
          </div>
        </header>

        {lowongan.deskripsi && <Bagian judul="Tentang Lowongan" isi={lowongan.deskripsi} />}

        {lowongan.kualifikasi && <Bagian judul="Kualifikasi" isi={lowongan.kualifikasi} />}

        {lowongan.cara_apply && <Bagian judul="Cara Melamar" isi={lowongan.cara_apply} />}

        <div style={{ marginTop: "2.5rem", padding: "1.5rem", border: "1px solid var(--border)", borderRadius: "12px", background: "var(--surface-hover)" }}>
          <p style={{ fontSize: "0.9rem", color: "var(--text-muted)", marginBottom: "1rem" }}>
            Informasi lowongan ini dihimpun dari <strong>{lowongan.sumber ?? "sumber publik"}</strong>. Pendaftaran dan
            proses seleksi dilakukan oleh pihak penyedia lowongan, bukan oleh TANGSEL LIA.
          </p>
          {lowongan.url_asli && (
            <a
              href={lowongan.url_asli}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-primary"
              style={{ gap: "0.5rem", display: "inline-flex" }}
            >
              <ExternalLink size={16} /> Buka Lowongan di {lowongan.sumber ?? "sumber asli"}
            </a>
          )}
          {lowongan.sumber_url && (
            <a
              href={lowongan.sumber_url}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-outline"
              style={{ gap: "0.5rem", display: "inline-flex", marginLeft: "0.75rem" }}
            >
              <ExternalLink size={16} /> Kunjungi {lowongan.sumber ?? "sumber"}
            </a>
          )}
        </div>
      </div>
    </article>
  );
}
