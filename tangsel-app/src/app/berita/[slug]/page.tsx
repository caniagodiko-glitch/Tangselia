import type { ReactNode } from "react";
import { notFound } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, CalendarDays, ExternalLink, Sparkles, User } from "lucide-react";
import { ApiError, getBeritaDetail, type Berita } from "@/lib/api";
import { formatTanggal } from "@/lib/format";

async function loadBerita(slug: string): Promise<Berita> {
  try {
    return (await getBeritaDetail(slug)).data;
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

export default async function BeritaDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const berita = await loadBerita(slug);

  const paragraphs = (berita.konten ?? "")
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean);

  return (
    <article style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <div className="container" style={{ maxWidth: "820px", paddingTop: "var(--spacing-lg)" }}>
        <Link
          href="/berita"
          style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem", color: "var(--text-muted)", fontWeight: 600, fontSize: "0.875rem" }}
        >
          <ArrowLeft size={16} /> Semua Berita
        </Link>

        <header style={{ margin: "2rem 0" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "0.75rem", marginBottom: "1rem", flexWrap: "wrap" }}>
            {berita.kategori && (
              <span style={{ color: "var(--primary)", fontSize: "0.75rem", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.05em" }}>
                {berita.kategori.nama}
              </span>
            )}
            {berita.tipe === "agregat" && (
              <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem", color: "var(--accent)", fontSize: "0.75rem", fontWeight: 700 }}>
                <Sparkles size={14} /> {berita.diringkas_oleh ?? "Diringkas AI"}
              </span>
            )}
          </div>

          <h1 style={{ fontFamily: "var(--font-display)", fontSize: "clamp(2rem, 4vw, 3rem)", lineHeight: 1.15, color: "#111", marginBottom: "1.25rem" }}>
            {berita.judul}
          </h1>

          <div style={{ display: "flex", alignItems: "center", gap: "1.25rem", flexWrap: "wrap", paddingBottom: "1.5rem", borderBottom: "1px solid var(--border)" }}>
            {berita.published_at && (
              <InfoItem icon={<CalendarDays size={15} />}>{formatTanggal(berita.published_at)}</InfoItem>
            )}
            <InfoItem icon={<User size={15} />}>
              {berita.sumber ?? berita.editor?.nama ?? "Redaksi TANGSEL LIA"}
            </InfoItem>
            {berita.kecamatan && <InfoItem icon={<span>•</span>}>{berita.kecamatan.nama}</InfoItem>}
          </div>
        </header>

        {berita.gambar && (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={berita.gambar}
            alt={berita.judul}
            style={{ width: "100%", maxHeight: "460px", objectFit: "cover", borderRadius: "12px", marginBottom: "2rem" }}
          />
        )}

        {berita.ringkasan && (
          <p style={{ fontSize: "1.25rem", lineHeight: 1.7, color: "var(--text-main)", fontWeight: 500, marginBottom: "1.5rem" }}>
            {berita.ringkasan}
          </p>
        )}

        <div style={{ display: "flex", flexDirection: "column", gap: "1.25rem" }}>
          {paragraphs.map((paragraph, index) => (
            <p key={index} style={{ color: "var(--text-main)", lineHeight: 1.85, fontSize: "1.05rem" }}>
              {paragraph}
            </p>
          ))}
        </div>

        {berita.tipe === "agregat" && berita.url_asli && (
          <div style={{ marginTop: "2rem", padding: "1.25rem", border: "1px solid var(--border)", borderRadius: "12px", background: "var(--surface-hover)" }}>
            <p style={{ fontSize: "0.9rem", color: "var(--text-muted)", marginBottom: "0.75rem" }}>
              Artikel ini merupakan ringkasan dari sumber eksternal. Baca selengkapnya di sumber aslinya.
            </p>
            <a
              href={berita.url_asli}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-outline"
              style={{ gap: "0.5rem", display: "inline-flex" }}
            >
              <ExternalLink size={16} /> {berita.sumber ?? "Buka sumber asli"}
            </a>
          </div>
        )}
      </div>
    </article>
  );
}
