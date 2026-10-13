import { Activity, ArrowRight, Coffee, Heart, MapPin, Newspaper, Search } from "lucide-react";
import type { CSSProperties } from "react";
import Link from "next/link";
import { getBeritaTerbaru, getLaporan, type Berita } from "@/lib/api";
import { LABEL_STATUS_LAPORAN, formatTanggal, labelOf, waktuRelatif } from "@/lib/format";

const STATUS_COLOR: Record<string, string> = {
  diverifikasi: "var(--primary-light)",
  diproses: "var(--warning)",
  selesai: "var(--success)",
  ditolak: "var(--error)",
};

function NewsThumb({ item, className, style }: { item: Berita; className?: string; style?: CSSProperties }) {
  if (item.gambar) {
    // eslint-disable-next-line @next/next/no-img-element
    return <img src={item.gambar} alt={item.judul} className={className} style={style} />;
  }

  return (
    <div
      className={className}
      style={{
        background: "linear-gradient(135deg, var(--primary), var(--primary-dark))",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        color: "rgba(255,255,255,0.75)",
        fontFamily: "var(--font-display)",
        fontSize: "1rem",
        textAlign: "center",
        padding: "1rem",
        ...style,
      }}
    >
      {item.kategori?.nama ?? "TANGSEL LIA"}
    </div>
  );
}

export default async function HomePage() {
  const [beritaRes, laporanRes] = await Promise.all([
    getBeritaTerbaru(4),
    getLaporan({ per_page: 3 }),
  ]);

  const berita = beritaRes.data;
  const featured = berita[0];
  const lainnya = berita.slice(1);
  const laporan = laporanRes.data;

  return (
    <div>
      {/* Hero Section */}
      <section className="hero">
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img
          src="https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&q=80&w=2000"
          alt="Tangerang Selatan Cityscape"
          className="hero-image"
        />
        <div className="container hero-content">
          <h1 className="hero-title">Tangsel, lebih dekat dalam satu tempat.</h1>
          <p className="hero-subtitle">
            Temukan tempat menarik, layanan kesehatan, berita terkini, dan sampaikan laporan untuk Tangsel.
          </p>
          <form action="/kuliner" method="get" className="search-bar">
            <input
              type="text"
              name="q"
              className="search-input"
              placeholder="Cari kuliner, layanan, atau informasi di Tangsel..."
            />
            <button type="submit" className="search-btn" aria-label="Cari">
              <Search size={20} />
            </button>
          </form>
        </div>
      </section>

      {/* Quick Access Section */}
      <section className="container" style={{ position: "relative", zIndex: 20 }}>
        <div className="quick-access-grid">
          <Link href="/kuliner" className="qa-card" style={{ padding: 0, overflow: "hidden", minHeight: "220px", position: "relative" }}>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&w=500&q=80"
              alt="Kuliner"
              style={{ width: "100%", height: "100%", objectFit: "cover", position: "absolute" }}
            />
            <div style={{ position: "absolute", inset: 0, background: "linear-gradient(to top, rgba(0,0,0,0.8), transparent)", display: "flex", flexDirection: "column", justifyContent: "flex-end", padding: "1.5rem", color: "white" }}>
              <Coffee size={28} style={{ marginBottom: "0.5rem", color: "var(--accent)" }} />
              <h3 className="qa-title" style={{ color: "white" }}>Kuliner</h3>
              <p style={{ fontSize: "0.85rem", opacity: 0.9 }}>Kafe, warkop & restoran</p>
            </div>
          </Link>

          <Link href="/olahraga" className="qa-card" style={{ background: "var(--primary)", color: "white", justifyContent: "space-between", minHeight: "220px" }}>
            <div style={{ background: "rgba(255,255,255,0.1)", padding: "1rem", borderRadius: "50%" }}>
              <Activity size={32} />
            </div>
            <div>
              <h3 className="qa-title" style={{ color: "white" }}>Tempat Olahraga</h3>
              <p style={{ fontSize: "0.85rem", opacity: 0.9, marginTop: "0.25rem" }}>Fasilitas & komunitas aktif</p>
            </div>
          </Link>

          <Link href="/kesehatan" className="qa-card" style={{ borderTop: "4px solid var(--accent)", justifyContent: "space-between", minHeight: "220px" }}>
            <div style={{ color: "var(--accent)" }}>
              <Heart size={32} />
            </div>
            <div>
              <h3 className="qa-title">Layanan Kesehatan</h3>
              <p style={{ fontSize: "0.85rem", color: "var(--text-muted)", marginTop: "0.25rem" }}>RS, Klinik, & Apotek Terdekat</p>
            </div>
          </Link>

          <Link href="/berita" className="qa-card" style={{ background: "#1a1a1a", color: "white", justifyContent: "space-between", minHeight: "220px" }}>
            <div style={{ display: "flex", justifyContent: "space-between", width: "100%" }}>
              <Newspaper size={32} color="var(--primary-light)" />
              <ArrowRight size={20} style={{ opacity: 0.5 }} />
            </div>
            <div>
              <div style={{ fontSize: "0.7rem", textTransform: "uppercase", letterSpacing: "0.1em", color: "var(--primary-light)", marginBottom: "0.5rem", fontWeight: 700 }}>Editorial</div>
              <h3 className="qa-title" style={{ color: "white", fontSize: "1.25rem" }}>Berita Tangsel</h3>
            </div>
          </Link>
        </div>
      </section>

      {/* Featured News Section */}
      <section className="section container">
        <div className="section-header">
          <div>
            <span className="tag" style={{ background: "var(--text-main)", marginBottom: "1rem" }}>TERKINI</span>
            <h2 className="section-title">Kabar Kota</h2>
          </div>
          <Link href="/berita" className="section-link">
            Lihat semua <ArrowRight size={18} />
          </Link>
        </div>

        {!featured ? (
          <div style={{ padding: "3rem 1.5rem", textAlign: "center", border: "1px dashed var(--border)", borderRadius: "12px", color: "var(--text-muted)" }}>
            Belum ada berita terbit.
          </div>
        ) : (
          <div className="discovery-grid">
            <Link href={`/berita/${featured.slug}`} className="featured-card" style={{ display: "block" }}>
              <NewsThumb item={featured} className="featured-img" style={{ minHeight: "400px" }} />
              <div className="featured-content">
                {featured.kategori && <span className="tag">{featured.kategori.nama}</span>}
                <h3 className="featured-title">{featured.judul}</h3>
                <div style={{ display: "flex", gap: "1rem", fontSize: "0.875rem", opacity: 0.8, marginTop: "1rem" }}>
                  <span>{featured.sumber ?? "Redaksi TANGSEL LIA"}</span>
                  <span>•</span>
                  <span>{featured.published_at ? waktuRelatif(featured.published_at) : ""}</span>
                </div>
              </div>
            </Link>

            <div className="list-grid">
              {lainnya.map((item) => (
                <Link key={item.id} href={`/berita/${item.slug}`} className="list-item" style={{ color: "inherit" }}>
                  <NewsThumb item={item} className="list-item-img" />
                  <div className="list-item-content">
                    {item.kategori && (
                      <span style={{ fontSize: "0.75rem", fontWeight: 600, color: "var(--primary)", marginBottom: "0.25rem" }}>
                        {item.kategori.nama}
                      </span>
                    )}
                    <h4 className="list-item-title">{item.judul}</h4>
                    <span className="list-item-meta">
                      {item.published_at ? formatTanggal(item.published_at) : ""}
                    </span>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
      </section>

      {/* Citizen Report Teaser */}
      <section className="section" style={{ background: "var(--surface-hover)", marginTop: "var(--spacing-xl)" }}>
        <div className="container">
          <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(300px, 1fr))", gap: "var(--spacing-xl)", alignItems: "center" }}>
            <div>
              <h2 className="section-title" style={{ marginBottom: "1rem" }}>Ada masalah di sekitar kamu?</h2>
              <p style={{ fontSize: "1.2rem", color: "var(--text-muted)", marginBottom: "2rem" }}>
                Laporkan fasilitas atau kondisi lingkungan yang membutuhkan perhatian. Kami pantau dan tindak lanjuti.
              </p>
              <Link href="/laporan/buat" className="btn btn-primary" style={{ fontSize: "1.1rem", padding: "1rem 2rem" }}>
                Buat Laporan Sekarang
              </Link>
            </div>

            <div style={{ background: "white", padding: "var(--spacing-lg)", borderRadius: "24px", boxShadow: "0 20px 40px rgba(0,0,0,0.05)" }}>
              <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1.5rem" }}>
                <h3 style={{ fontSize: "1.25rem" }}>Pantau Aduan Sekitar</h3>
                <Link href="/laporan" style={{ fontSize: "0.85rem", fontWeight: 600, color: "var(--primary)" }}>Semua</Link>
              </div>
              <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
                {laporan.length === 0 ? (
                  <p style={{ color: "var(--text-muted)", fontSize: "0.95rem" }}>Belum ada laporan terverifikasi.</p>
                ) : (
                  laporan.map((item) => (
                    <Link
                      key={item.id}
                      href="/laporan"
                      style={{
                        padding: "1rem",
                        borderLeft: `3px solid ${STATUS_COLOR[item.status] ?? "var(--border)"}`,
                        background: "var(--surface-hover)",
                        borderRadius: "0 8px 8px 0",
                        display: "block",
                        color: "inherit",
                      }}
                    >
                      <div style={{ fontSize: "0.75rem", fontWeight: 600, color: STATUS_COLOR[item.status] ?? "var(--text-muted)", marginBottom: "0.25rem", textTransform: "uppercase" }}>
                        {labelOf(LABEL_STATUS_LAPORAN, item.status)}
                        {item.kecamatan ? ` • ${item.kecamatan.nama}` : ""}
                      </div>
                      <h4 style={{ fontSize: "1rem", marginBottom: "0.35rem" }}>{item.judul}</h4>
                      <div style={{ display: "flex", alignItems: "center", gap: "0.35rem", fontSize: "0.75rem", color: "var(--text-light)" }}>
                        <MapPin size={12} /> {item.kode}
                      </div>
                    </Link>
                  ))
                )}
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
