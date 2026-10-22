import { Activity, ArrowRight, Building2, Coffee, Heart, Newspaper, Search } from "lucide-react";
import type { CSSProperties } from "react";
import Link from "next/link";
import { getBeritaTerbaru, getLowonganTerbaru, type Berita } from "@/lib/api";
import { LABEL_JENIS_LOWONGAN, formatTanggal, waktuRelatif } from "@/lib/format";

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
  const [beritaRes, lowonganRes] = await Promise.all([
    getBeritaTerbaru(4),
    getLowonganTerbaru(4),
  ]);

  const berita = beritaRes.data;
  const featured = berita[0];
  const lainnya = berita.slice(1);
  const lowongan = lowonganRes.data;

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
            Temukan tempat menarik, layanan kesehatan, berita terkini, dan lowongan kerja di Tangsel.
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

      {/* Lowongan Terbaru Section */}
      <section className="section" style={{ background: "var(--surface-hover)", marginTop: "var(--spacing-xl)" }}>
        <div className="container">
          <div className="section-header">
            <div>
              <span className="tag" style={{ background: "var(--primary)", color: "white", marginBottom: "1rem" }}>
                KARIER
              </span>
              <h2 className="section-title">Lowongan Terbaru</h2>
              <p style={{ fontSize: "1rem", color: "var(--text-muted)", maxWidth: "640px", marginTop: "0.75rem" }}>
                Lowongan kerja dan magang di Tangerang Selatan yang dihimpun dari job board dan pemberita, lalu
                dikurasi oleh admin TANGSEL LIA.
              </p>
            </div>
            <Link href="/lowongan" className="section-link">
              Lihat semua <ArrowRight size={18} />
            </Link>
          </div>

          {lowongan.length === 0 ? (
            <div style={{ padding: "3rem 1.5rem", textAlign: "center", border: "1px dashed var(--border)", borderRadius: "12px", color: "var(--text-muted)" }}>
              Belum ada lowongan yang tayang.
            </div>
          ) : (
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(280px, 1fr))", gap: "1.5rem" }}>
              {lowongan.map((item) => (
                <Link
                  key={item.id}
                  href={`/lowongan/${item.slug}`}
                  style={{
                    background: "white",
                    padding: "1.5rem",
                    borderRadius: "16px",
                    boxShadow: "0 4px 12px rgba(0,0,0,0.04)",
                    display: "flex",
                    flexDirection: "column",
                    gap: "0.6rem",
                    color: "inherit",
                  }}
                >
                  <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", flexWrap: "wrap" }}>
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
                      {LABEL_JENIS_LOWONGAN[item.jenis] ?? item.jenis}
                    </span>
                    {item.kategori && (
                      <span style={{ fontSize: "0.75rem", color: "var(--text-muted)", fontWeight: 600 }}>{item.kategori.nama}</span>
                    )}
                  </div>

                  <h3 style={{ fontFamily: "var(--font-display)", fontSize: "1.1rem", lineHeight: 1.35, color: "#111" }}>
                    {item.posisi ?? item.judul}
                  </h3>

                  {item.perusahaan && (
                    <p style={{ display: "flex", alignItems: "center", gap: "0.35rem", fontSize: "0.85rem", color: "var(--text-muted)", fontWeight: 600 }}>
                      <Building2 size={14} /> {item.perusahaan}
                    </p>
                  )}

                  <div
                    style={{
                      marginTop: "auto",
                      paddingTop: "0.75rem",
                      borderTop: "1px solid var(--border)",
                      fontSize: "0.75rem",
                      color: "var(--text-light)",
                      fontWeight: 600,
                    }}
                  >
                    {item.sumber ?? "TANGSEL LIA"}
                    {item.published_at ? ` • ${formatTanggal(item.published_at)}` : ""}
                  </div>
                </Link>
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
}
