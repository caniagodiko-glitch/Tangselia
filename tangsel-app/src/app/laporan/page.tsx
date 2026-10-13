import { AlertTriangle, ChevronRight, Camera, MapPin, Signpost } from "lucide-react";
import Link from "next/link";
import { getLaporan, type Laporan, type Paginated } from "@/lib/api";
import { authedFetch, getCurrentUser } from "@/lib/auth";
import { LABEL_STATUS_LAPORAN, formatTanggal, labelOf } from "@/lib/format";

type SearchParams = Record<string, string | string[] | undefined>;

const str = (value: string | string[] | undefined): string | undefined => {
  const raw = Array.isArray(value) ? value[0] : value;
  return raw?.trim() || undefined;
};

const STATUS_COLOR: Record<string, string> = {
  baru: "var(--text-muted)",
  diverifikasi: "var(--primary-light)",
  diproses: "var(--warning)",
  selesai: "var(--success)",
  ditolak: "var(--error)",
};

function LaporanCard({ item }: { item: Laporan }) {
  const color = STATUS_COLOR[item.status] ?? "var(--text-muted)";

  return (
    <div style={{ background: "#fff", border: "1px solid var(--border)", borderRadius: "16px", padding: "1.25rem" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "0.75rem", marginBottom: "0.75rem", flexWrap: "wrap" }}>
        <span style={{ color, fontSize: "0.75rem", fontWeight: 700, textTransform: "uppercase", letterSpacing: "0.05em" }}>
          {labelOf(LABEL_STATUS_LAPORAN, item.status)}
        </span>
        <span style={{ fontSize: "0.75rem", color: "var(--text-light)", fontWeight: 600 }}>{item.kode}</span>
      </div>

      <h3 style={{ fontSize: "1.15rem", marginBottom: "0.5rem" }}>{item.judul}</h3>
      <p style={{ color: "var(--text-muted)", fontSize: "0.95rem", lineHeight: 1.6, marginBottom: "1rem" }}>{item.deskripsi}</p>

      <div style={{ display: "flex", flexWrap: "wrap", gap: "1rem", fontSize: "0.8rem", color: "var(--text-light)" }}>
        {item.kategori && (
          <span style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}>
            <Signpost size={13} /> {item.kategori.nama}
          </span>
        )}
        {item.kecamatan && (
          <span style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}>
            <MapPin size={13} /> {item.kecamatan.nama}
          </span>
        )}
        {item.foto.length > 0 && (
          <span style={{ display: "inline-flex", alignItems: "center", gap: "0.35rem" }}>
            <Camera size={13} /> {item.foto.length} foto
          </span>
        )}
        <span>{item.created_at ? formatTanggal(item.created_at) : ""}</span>
      </div>
    </div>
  );
}

export default async function LaporanWarga({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const sp = await searchParams;
  const tab = sp.tab === "mine" ? "mine" : "publik";
  const cursor = str(sp.cursor);
  const user = await getCurrentUser();

  let list: Paginated<Laporan> | null = null;
  let error: string | null = null;

  if (tab === "mine" && user) {
    try {
      list = await authedFetch<Paginated<Laporan>>("/laporan", { mine: 1, per_page: 12, cursor });
    } catch {
      error = "Gagal memuat laporan kamu.";
    }
  } else if (tab === "publik") {
    list = await getLaporan({ per_page: 12, cursor });
  }

  const nextCursor = list?.meta.next_cursor ?? null;
  const buildHref = (patch: { tab?: string; cursor?: string }): string => {
    const params = new URLSearchParams();
    const nextTab = patch.tab ?? tab;
    if (nextTab === "mine") params.set("tab", "mine");
    if (patch.cursor) params.set("cursor", patch.cursor);
    const qs = params.toString();
    return qs ? `/laporan?${qs}` : "/laporan";
  };

  return (
    <div style={{ background: "var(--surface-hover)", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <section className="container" style={{ paddingTop: "var(--spacing-xl)", paddingBottom: "var(--spacing-lg)" }}>
        <div style={{ textAlign: "center", maxWidth: "700px", margin: "0 auto" }}>
          <div style={{ width: "64px", height: "64px", background: "#fee2e2", color: "#dc2626", borderRadius: "20px", display: "flex", alignItems: "center", justifyContent: "center", margin: "0 auto 1.5rem", transform: "rotate(-5deg)" }}>
            <AlertTriangle size={32} />
          </div>
          <h1 className="hero-title" style={{ fontSize: "clamp(2.5rem, 4vw, 3.5rem)", color: "#111", marginBottom: "1rem" }}>
            Ada masalah di
            <br />
            sekitar kamu?
          </h1>
          <p style={{ fontSize: "1.25rem", color: "var(--text-muted)", marginBottom: "2rem" }}>
            Laporkan fasilitas atau kondisi lingkungan yang membutuhkan perhatian. Kami terhubung langsung dengan dinas terkait di Tangerang Selatan.
          </p>
          <Link
            href="/laporan/buat"
            className="btn btn-primary"
            style={{ padding: "1.25rem 3rem", fontSize: "1.1rem", borderRadius: "12px", gap: "0.75rem", boxShadow: "0 10px 25px rgba(2, 92, 83, 0.2)" }}
          >
            Buat Laporan <ChevronRight size={20} />
          </Link>
        </div>
      </section>

      <section className="container" style={{ maxWidth: "880px", margin: "0 auto" }}>
        <div style={{ display: "flex", gap: "0.5rem", marginBottom: "1.5rem" }}>
          <Link
            href={buildHref({ tab: "publik", cursor: undefined })}
            style={{
              padding: "0.6rem 1.25rem",
              borderRadius: "999px",
              fontWeight: 600,
              fontSize: "0.9rem",
              border: "1px solid var(--border)",
              background: tab === "publik" ? "var(--primary)" : "#fff",
              color: tab === "publik" ? "#fff" : "var(--text-muted)",
            }}
          >
            Feed Publik
          </Link>
          <Link
            href={user ? buildHref({ tab: "mine", cursor: undefined }) : "/login?next=/laporan"}
            style={{
              padding: "0.6rem 1.25rem",
              borderRadius: "999px",
              fontWeight: 600,
              fontSize: "0.9rem",
              border: "1px solid var(--border)",
              background: tab === "mine" ? "var(--primary)" : "#fff",
              color: tab === "mine" ? "#fff" : "var(--text-muted)",
            }}
          >
            Laporan Saya
          </Link>
        </div>

        {tab === "mine" && !user ? (
          <div style={{ background: "#fff", border: "1px solid var(--border)", borderRadius: "16px", padding: "2.5rem 1.5rem", textAlign: "center" }}>
            <p style={{ color: "var(--text-muted)", marginBottom: "1rem" }}>Masuk dulu untuk melihat laporan kamu.</p>
            <Link href="/login?next=/laporan" className="btn btn-primary">
              Masuk
            </Link>
          </div>
        ) : (
          <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            {error && <p style={{ color: "var(--error)" }}>{error}</p>}

            {list && list.data.length === 0 && (
              <div style={{ background: "#fff", border: "1px dashed var(--border)", borderRadius: "16px", padding: "2.5rem 1.5rem", textAlign: "center", color: "var(--text-muted)" }}>
                {tab === "mine" ? "Kamu belum pernah mengirim laporan." : "Belum ada laporan terverifikasi."}
              </div>
            )}

            {list?.data.map((item) => <LaporanCard key={item.id} item={item} />)}

            {nextCursor && (
              <Link href={buildHref({ cursor: nextCursor })} className="btn btn-outline" style={{ alignSelf: "center", marginTop: "0.5rem" }}>
                Muat lebih banyak
              </Link>
            )}
          </div>
        )}
      </section>
    </div>
  );
}
