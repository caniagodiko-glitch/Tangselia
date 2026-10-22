import { Briefcase, Building2, ExternalLink, MapPin, Search, ArrowRight } from "lucide-react";
import Link from "next/link";
import { getLowongan, getLowonganMeta, type Lowongan } from "@/lib/api";
import { LABEL_JENIS_LOWONGAN, formatTanggal } from "@/lib/format";

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

function Badge({ jenis }: { jenis: Lowongan["jenis"] }) {
  const warna =
    jenis === "magang"
      ? { color: "#7c3aed", bg: "#ede9fe" }
      : jenis === "part-time"
        ? { color: "#b45309", bg: "#fef3c7" }
        : { color: "var(--primary)", bg: "#e6f4f1" };

  return (
    <span
      style={{
        fontSize: "0.7rem",
        fontWeight: 700,
        textTransform: "uppercase",
        letterSpacing: "0.08em",
        color: warna.color,
        background: warna.bg,
        padding: "0.2rem 0.7rem",
        borderRadius: "20px",
        whiteSpace: "nowrap",
      }}
    >
      {LABEL_JENIS_LOWONGAN[jenis] ?? jenis}
    </span>
  );
}

function LowonganCard({ item }: { item: Lowongan }) {
  return (
    <article
      style={{
        border: "1px solid var(--border)",
        borderRadius: "16px",
        padding: "1.5rem",
        display: "flex",
        flexDirection: "column",
        gap: "0.85rem",
        background: "white",
      }}
    >
      <div style={{ display: "flex", gap: "0.5rem", alignItems: "center", flexWrap: "wrap" }}>
        <Badge jenis={item.jenis} />
        {item.kategori && (
          <span style={{ fontSize: "0.75rem", color: "var(--text-muted)", fontWeight: 600 }}>
            {item.kategori.nama}
          </span>
        )}
        {item.kecamatan && (
          <span style={{ display: "inline-flex", alignItems: "center", gap: "0.25rem", fontSize: "0.75rem", color: "var(--text-light)" }}>
            <MapPin size={12} /> {item.kecamatan.nama}
          </span>
        )}
        <span style={{ marginLeft: "auto", fontSize: "0.72rem", color: "var(--text-light)", fontWeight: 600 }}>
          {item.kode}
        </span>
      </div>

      <div>
        <h3 style={{ fontFamily: "var(--font-display)", fontSize: "1.35rem", lineHeight: 1.3, color: "#111", marginBottom: "0.35rem" }}>
          {item.posisi ?? item.judul}
        </h3>
        {item.posisi && (
          <p style={{ fontSize: "0.95rem", color: "var(--text-muted)", marginBottom: "0.2rem" }}>{item.judul}</p>
        )}
        {item.perusahaan && (
          <p style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem", fontSize: "0.9rem", fontWeight: 600, color: "var(--text-main)" }}>
            <Building2 size={14} /> {item.perusahaan}
          </p>
        )}
      </div>

      {item.deskripsi && (
        <p style={{ fontSize: "0.9rem", lineHeight: 1.65, color: "var(--text-muted)" }}>{item.deskripsi}</p>
      )}

      <div
        style={{
          marginTop: "auto",
          paddingTop: "1rem",
          borderTop: "1px solid var(--border)",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          gap: "1rem",
          flexWrap: "wrap",
        }}
      >
        <span style={{ fontSize: "0.75rem", color: "var(--text-light)", fontWeight: 600 }}>
          {item.sumber ?? "TANGSEL LIA"}
          {item.published_at ? ` • ${formatTanggal(item.published_at)}` : ""}
        </span>

        <span style={{ display: "flex", gap: "0.75rem", alignItems: "center", flexWrap: "wrap" }}>
          <Link href={`/lowongan/${item.slug}`} style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem", fontSize: "0.85rem", fontWeight: 700, color: "var(--primary)" }}>
            Detail <ArrowRight size={14} />
          </Link>
          {item.url_asli && (
            <a
              href={item.url_asli}
              target="_blank"
              rel="noopener noreferrer"
              className="btn btn-outline"
              style={{ gap: "0.4rem", display: "inline-flex", padding: "0.5rem 1rem", fontSize: "0.85rem" }}
            >
              <ExternalLink size={14} /> Buka Lowongan
            </a>
          )}
        </span>
      </div>
    </article>
  );
}

export default async function LowonganPage({ searchParams }: { searchParams: Promise<SearchParams> }) {
  const sp = await searchParams;
  const current = {
    jenis: str(sp.jenis),
    kategori: str(sp.kategori),
    kecamatan: str(sp.kecamatan),
    q: str(sp.q),
  };

  const [list, meta] = await Promise.all([
    getLowongan({ ...current, per_page: 12 }),
    getLowonganMeta(),
  ]);

  const buildHref = (patch: Partial<typeof current>, cursor?: string): string => {
    const merged = { ...current, ...patch };
    const params = new URLSearchParams();
    if (merged.jenis) params.set("jenis", merged.jenis);
    if (merged.kategori) params.set("kategori", merged.kategori);
    if (merged.kecamatan) params.set("kecamatan", merged.kecamatan);
    if (merged.q) params.set("q", merged.q);
    if (cursor) params.set("cursor", cursor);
    const qs = params.toString();
    return qs ? `/lowongan?${qs}` : "/lowongan";
  };

  const cursorBerikut = cursorOf(list.links.next);
  const cursorSebelumnya = cursorOf(list.links.prev);

  return (
    <div style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <section
        className="container"
        style={{ padding: "var(--spacing-xl) 0", borderBottom: "1px solid var(--border)", marginBottom: "var(--spacing-xl)" }}
      >
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", flexWrap: "wrap", gap: "2rem" }}>
          <div>
            <h1
              className="hero-title"
              style={{ fontFamily: "var(--font-display)", fontSize: "clamp(2.5rem, 4.5vw, 4rem)", letterSpacing: "-0.03em", color: "#111", lineHeight: 1 }}
            >
              LOWONGAN
              <br />
              KERJA &amp; MAGANG.
            </h1>
            <p
              style={{
                fontSize: "1rem",
                color: "var(--text-muted)",
                marginTop: "1rem",
                fontWeight: 500,
                letterSpacing: "0.05em",
                textTransform: "uppercase",
              }}
            >
              Kurasi Lowongan di Tangerang Selatan
            </p>
          </div>

          <form action="/lowongan" method="get" className="search-bar" style={{ maxWidth: "360px", border: "1px solid var(--border)" }}>
            {current.jenis && <input type="hidden" name="jenis" value={current.jenis} />}
            {current.kategori && <input type="hidden" name="kategori" value={current.kategori} />}
            {current.kecamatan && <input type="hidden" name="kecamatan" value={current.kecamatan} />}
            <input type="text" name="q" defaultValue={current.q ?? ""} className="search-input" placeholder="Cari posisi atau perusahaan..." />
            <button type="submit" className="search-btn" aria-label="Cari lowongan">
              <Search size={18} />
            </button>
          </form>
        </div>

        <div style={{ display: "flex", gap: "1rem", flexWrap: "wrap", marginTop: "1.5rem", fontFamily: "var(--font-display)", fontWeight: 600, fontSize: "0.875rem" }}>
          <Link href={buildHref({ jenis: undefined })} style={{ color: current.jenis ? "var(--text-muted)" : "#111" }}>
            Semua
          </Link>
          {meta.data.jenis.map((item) => (
            <Link key={item.value} href={buildHref({ jenis: item.value })} style={{ color: current.jenis === item.value ? "#111" : "var(--text-muted)" }}>
              {LABEL_JENIS_LOWONGAN[item.value] ?? item.value} ({item.jumlah})
            </Link>
          ))}
        </div>
      </section>

      <section className="container">
        <form action="/lowongan" method="get" className="lowongan-filter">
          {current.jenis && <input type="hidden" name="jenis" value={current.jenis} />}
          {current.q && <input type="hidden" name="q" value={current.q} />}

          <div className="form-group" style={{ marginBottom: 0 }}>
            <label className="form-label" htmlFor="filter-kategori">Bidang Pekerjaan</label>
            <select id="filter-kategori" name="kategori" className="form-control" defaultValue={current.kategori ?? ""}>
              <option value="">Semua bidang</option>
              {meta.data.kategori.map((item) => (
                <option key={item.slug} value={item.slug}>
                  {item.nama} ({item.jumlah})
                </option>
              ))}
            </select>
          </div>

          <div className="form-group" style={{ marginBottom: 0 }}>
            <label className="form-label" htmlFor="filter-kecamatan">Kecamatan</label>
            <select id="filter-kecamatan" name="kecamatan" className="form-control" defaultValue={current.kecamatan ?? ""}>
              <option value="">Semua kecamatan</option>
              {meta.data.kecamatan.map((item) => (
                <option key={item.slug} value={item.slug}>
                  {item.nama} ({item.jumlah})
                </option>
              ))}
            </select>
          </div>

          <button type="submit" className="btn btn-primary" style={{ marginBottom: 0, cursor: "pointer" }}>
            <Briefcase size={16} /> Terapkan
          </button>
        </form>

        {list.data.length === 0 ? (
          <div style={{ padding: "4rem 1.5rem", textAlign: "center", border: "1px dashed var(--border)", borderRadius: "12px", color: "var(--text-muted)" }}>
            Belum ada lowongan yang cocok dengan filter ini.
          </div>
        ) : (
          <>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(320px, 1fr))", gap: "1.5rem", marginTop: "var(--spacing-lg)" }}>
              {list.data.map((item) => (
                <LowonganCard key={item.id} item={item} />
              ))}
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
                Menampilkan {list.data.length} lowongan
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
        .lowongan-filter {
          display: flex;
          gap: 1rem;
          align-items: flex-end;
          flex-wrap: wrap;
          padding: 1.25rem;
          border: 1px solid var(--border);
          border-radius: 12px;
          background: var(--surface-hover);
        }
        .lowongan-filter .form-group { min-width: 220px; flex: 1 1 220px; }
        @media (max-width: 639px) {
          .lowongan-filter .form-group { min-width: 100%; }
        }
      `,
        }}
      />
    </div>
  );
}
