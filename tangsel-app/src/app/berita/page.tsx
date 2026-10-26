import { Search, ArrowRight, Sparkles } from "lucide-react";
import Link from "next/link";
import { getBerita, getBeritaMeta, getBeritaTerbaru, type Berita } from "@/lib/api";
import { formatTanggal, waktuRelatif } from "@/lib/format";

type SearchParams = Record<string, string | string[] | undefined>;

const str = (value: string | string[] | undefined): string | undefined => {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw?.trim() || undefined;
};

/**
 * Ambil nilai cursor dari tautan paginasi API.
 *
 * API mengembalikan tautan lengkap ke backend, sedangkan halaman ini
 * memakai tautan internal. Yang dibutuhkan hanya nilai cursor-nya.
 */
function cursorOf(link: string | null): string | undefined {
  if (!link) {
    return undefined;
  }

  const url = new URL(link, "http://localhost");
  return url.searchParams.get("cursor") ?? undefined;
}

function Badge({ children, color = "var(--primary)" }: { children: React.ReactNode; color?: string }) {
  return (
    <span style={{ color, fontSize: "0.75rem", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.05em" }}>
      {children}
    </span>
  );
}

function Meta({ item }: { item: Berita }) {
  return (
    <div style={{ display: "flex", alignItems: "center", gap: "0.6rem", flexWrap: "wrap", fontSize: "0.75rem", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.05em" }}>
      {item.kategori && <Badge>{item.kategori.nama}</Badge>}
      <span style={{ color: "var(--text-light)", textTransform: "none", letterSpacing: 0, fontWeight: 500 }}>
        {item.published_at ? waktuRelatif(item.published_at) : ""}
      </span>
      {item.tipe === "agregat" && (
        <span style={{ display: "inline-flex", alignItems: "center", gap: "0.25rem", color: "var(--accent)", textTransform: "none", letterSpacing: 0 }}>
          <Sparkles size={13} /> {item.diringkas_oleh ?? "Diringkas"}
        </span>
      )}
    </div>
  );
}

function Thumb({ item, height, width }: { item: Berita; height: string; width?: string }) {
  return (
    <div style={{ width: width ?? "100%", height, flexShrink: 0, borderRadius: "4px", overflow: "hidden", background: "linear-gradient(135deg, var(--primary), var(--primary-dark))", display: "flex", alignItems: "center", justifyContent: "center" }}>
      {item.gambar ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img src={item.gambar} alt={item.judul} style={{ width: "100%", height: "100%", objectFit: "cover" }} />
      ) : (
        <span style={{ color: "rgba(255,255,255,0.7)", fontFamily: "var(--font-display)", fontSize: "1rem", padding: "1rem", textAlign: "center" }}>
          {item.kategori?.nama ?? "Berita"}
        </span>
      )}
    </div>
  );
}

export default async function BeritaPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const sp = await searchParams;
  const current = {
    kategori: str(sp.kategori),
    kecamatan: str(sp.kecamatan),
    tipe: str(sp.tipe),
    sumber: str(sp.sumber),
    q: str(sp.q),
  };
  const cursor = str(sp.cursor);

  const [list, meta, terbaru] = await Promise.all([
    getBerita({ ...current, cursor, per_page: 12 }),
    getBeritaMeta(),
    getBeritaTerbaru(5),
  ]);

  const categories = meta.data.kategori;

  const buildHref = (patch: Partial<typeof current>, targetCursor?: string): string => {
    const merged = { ...current, ...patch };
    const params = new URLSearchParams();
    if (merged.kategori) params.set("kategori", merged.kategori);
    if (merged.kecamatan) params.set("kecamatan", merged.kecamatan);
    if (merged.tipe) params.set("tipe", merged.tipe);
    if (merged.sumber) params.set("sumber", merged.sumber);
    if (merged.q) params.set("q", merged.q);
    if (targetCursor) params.set("cursor", targetCursor);
    const qs = params.toString();
    return qs ? `/berita?${qs}` : "/berita";
  };

  const cursorBerikut = cursorOf(list.links.next);
  const cursorSebelumnya = cursorOf(list.links.prev);

  // Sorotan besar hanya di halaman pertama; halaman berikutnya tampil rata.
  const featured = cursor ? undefined : list.data[0];
  const rest = cursor ? list.data : list.data.slice(1);
  const trending = terbaru.data.slice(0, 5);

  return (
    <div style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <section className="container" style={{ padding: "var(--spacing-xl) 0", borderBottom: "1px solid var(--border)", marginBottom: "var(--spacing-xl)" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", flexWrap: "wrap", gap: "2rem" }}>
          <div>
            <h1 className="hero-title" style={{ fontFamily: "var(--font-display)", fontSize: "clamp(3rem, 5vw, 4.5rem)", letterSpacing: "-0.03em", color: "#111", lineHeight: 1 }}>
              TANGSEL
              <br />
              HARI INI.
            </h1>
            <p style={{ fontSize: "1rem", color: "var(--text-muted)", marginTop: "1rem", fontWeight: 500, letterSpacing: "0.05em", textTransform: "uppercase" }}>
              Kurasi Jurnalisme Lokal Tangerang Selatan
            </p>
          </div>

          <form action="/berita" method="get" className="search-bar" style={{ maxWidth: "360px", border: "1px solid var(--border)" }}>
            {current.kategori && <input type="hidden" name="kategori" value={current.kategori} />}
            {current.kecamatan && <input type="hidden" name="kecamatan" value={current.kecamatan} />}
            {current.tipe && <input type="hidden" name="tipe" value={current.tipe} />}
            {current.sumber && <input type="hidden" name="sumber" value={current.sumber} />}
            <input type="text" name="q" defaultValue={current.q ?? ""} className="search-input" placeholder="Cari berita..." />
            <button type="submit" className="search-btn" aria-label="Cari berita">
              <Search size={18} />
            </button>
          </form>
        </div>

        <div style={{ display: "flex", gap: "1rem", flexWrap: "wrap", marginTop: "1.5rem", fontFamily: "var(--font-display)", fontWeight: 600, fontSize: "0.875rem" }}>
          <Link href={buildHref({ kategori: undefined })} style={{ color: current.kategori ? "var(--text-muted)" : "#111" }}>
            Semua
          </Link>
          {categories.map((kategori) => (
            <Link
              key={kategori.slug}
              href={buildHref({ kategori: kategori.slug })}
              style={{ color: current.kategori === kategori.slug ? "#111" : "var(--text-muted)" }}
            >
              {kategori.nama}
            </Link>
          ))}
        </div>
      </section>

      <section className="container">
        {list.data.length === 0 ? (
          <div style={{ padding: "4rem 1.5rem", textAlign: "center", border: "1px dashed var(--border)", borderRadius: "12px", color: "var(--text-muted)" }}>
            Belum ada berita yang cocok dengan filter ini.
          </div>
        ) : (
          <>
            {featured && (
              <div style={{ marginBottom: "var(--spacing-xl)", display: "grid", gridTemplateColumns: "1fr", gap: "2rem" }}>
                <Thumb item={featured} height="520px" />
                <div style={{ maxWidth: "800px", margin: "0 auto", textAlign: "center" }}>
                  <div style={{ display: "flex", alignItems: "center", justifyContent: "center", gap: "1rem", marginBottom: "1.5rem" }}>
                    <Meta item={featured} />
                  </div>
                  <h2 style={{ fontSize: "clamp(2rem, 4vw, 3.5rem)", fontFamily: "var(--font-display)", lineHeight: 1.1, color: "#111", marginBottom: "1.5rem" }}>
                    {featured.judul}
                  </h2>
                  {featured.ringkasan && (
                    <p style={{ fontSize: "1.25rem", color: "var(--text-muted)", lineHeight: 1.6, marginBottom: "2rem" }}>{featured.ringkasan}</p>
                  )}
                  <Link
                    href={`/berita/${featured.slug}`}
                    className="btn"
                    style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem", borderBottom: "2px solid #111", borderRadius: 0, padding: "0.5rem 0", fontWeight: 700, letterSpacing: "0.05em", color: "#111" }}
                  >
                    BACA ARTIKEL LENGKAP <ArrowRight size={16} />
                  </Link>
                </div>
              </div>
            )}

            <hr style={{ border: "none", borderTop: "1px solid var(--border)", margin: "var(--spacing-xl) 0" }} />

            <div style={{ display: "grid", gridTemplateColumns: "repeat(12, 1fr)", gap: "var(--spacing-lg)" }}>
              <div style={{ gridColumn: "span 8", display: "flex", flexDirection: "column", gap: "var(--spacing-lg)" }} className="news-main-feed">
                {rest.map((item) => (
                  <Link key={item.id} href={`/berita/${item.slug}`} style={{ display: "flex", gap: "2rem", alignItems: "flex-start", color: "inherit" }} className="news-list-item">
                    <div style={{ flex: "1" }}>
                      <div style={{ marginBottom: "0.75rem" }}>
                        <Meta item={item} />
                      </div>
                      <h3 style={{ fontSize: "1.5rem", fontFamily: "var(--font-display)", lineHeight: 1.3, marginBottom: "1rem", color: "#111" }}>{item.judul}</h3>
                      {item.ringkasan && <p style={{ color: "var(--text-muted)", lineHeight: 1.6, marginBottom: "1rem" }}>{item.ringkasan}</p>}
                      <div style={{ fontSize: "0.875rem", fontWeight: 600, color: "var(--text-main)" }}>
                        {item.sumber ?? item.editor?.nama ?? "Redaksi TANGSEL LIA"}
                      </div>
                    </div>
                    <Thumb item={item} height="200px" width="280px" />
                  </Link>
                ))}
              </div>

              <div style={{ gridColumn: "span 4" }} className="news-sidebar">
                <h3 style={{ fontFamily: "var(--font-display)", fontSize: "1.25rem", borderBottom: "2px solid #111", paddingBottom: "1rem", marginBottom: "1.5rem" }}>
                  TERBARU
                </h3>
                <div style={{ display: "flex", flexDirection: "column", gap: "1.5rem" }}>
                  {trending.map((item, index) => (
                    <Link key={item.id} href={`/berita/${item.slug}`} style={{ display: "flex", gap: "1rem", color: "inherit" }}>
                      <div style={{ fontSize: "2rem", fontFamily: "var(--font-display)", fontWeight: 300, color: "var(--border)", lineHeight: 1 }}>
                        {String(index + 1).padStart(2, "0")}
                      </div>
                      <div>
                        {item.kategori && (
                          <div style={{ fontSize: "0.75rem", fontWeight: 700, color: "var(--accent)", marginBottom: "0.25rem", textTransform: "uppercase" }}>
                            {item.kategori.nama}
                          </div>
                        )}
                        <h4 style={{ fontSize: "1.1rem", fontFamily: "var(--font-display)", lineHeight: 1.3, color: "#111" }}>{item.judul}</h4>
                        <div style={{ fontSize: "0.75rem", color: "var(--text-light)", marginTop: "0.35rem" }}>
                          {item.published_at ? formatTanggal(item.published_at) : ""}
                        </div>
                      </div>
                    </Link>
                  ))}
                </div>
              </div>
            </div>

            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "1rem", marginTop: "var(--spacing-xl)", flexWrap: "wrap" }}>
              {cursorSebelumnya ? (
                <Link href={buildHref({}, cursorSebelumnya)} className="btn btn-outline">
                  <ArrowRight size={16} style={{ transform: "rotate(180deg)" }} /> Sebelumnya
                </Link>
              ) : (
                <span />
              )}

              <span style={{ fontSize: "0.8rem", color: "var(--text-light)", fontWeight: 600 }}>
                Menampilkan {list.data.length} berita
              </span>

              {cursorBerikut ? (
                <Link href={buildHref({}, cursorBerikut)} className="btn btn-outline">
                  Berikutnya <ArrowRight size={16} />
                </Link>
              ) : (
                <span />
              )}
            </div>
          </>
        )}
      </section>

      <style
        dangerouslySetInnerHTML={{
          __html: `
        @media (max-width: 1023px) {
          .news-main-feed, .news-sidebar { grid-column: span 12 !important; }
          .news-list-item { flex-direction: column-reverse; }
          .news-list-item > div:last-child { width: 100% !important; height: 250px !important; }
        }
      `,
        }}
      />
    </div>
  );
}
