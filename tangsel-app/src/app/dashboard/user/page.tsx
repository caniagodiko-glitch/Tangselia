import { redirect } from "next/navigation";
import Link from "next/link";
import { BarChart3, ChevronRight, PlusCircle } from "lucide-react";
import LogoutButton from "@/components/auth/LogoutButton";
import { authedFetch, getCurrentUser } from "@/lib/auth";
import type { Laporan, Paginated } from "@/lib/api";
import { LABEL_STATUS_LAPORAN, formatTanggal, labelOf } from "@/lib/format";

const STATUS_STYLE: Record<string, { color: string; bg: string }> = {
  baru: { color: "#475569", bg: "#f1f5f9" },
  diverifikasi: { color: "#3b82f6", bg: "#eff6ff" },
  diproses: { color: "#d97706", bg: "#fef3c7" },
  selesai: { color: "#10b981", bg: "#dcfce7" },
  ditolak: { color: "#dc2626", bg: "#fee2e2" },
};

export default async function UserDashboard() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login?next=/dashboard/user");
  }

  const laporanRes = await authedFetch<Paginated<Laporan>>("/laporan", { mine: 1, per_page: 50 });
  const laporan = laporanRes.data;
  const hitung = (status: string) => laporan.filter((item) => item.status === status).length;
  const inisial = user.nama
    .split(" ")
    .slice(0, 2)
    .map((kata) => kata.charAt(0).toUpperCase())
    .join("");

  return (
    <div style={{ background: "var(--surface-hover)", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <section style={{ background: "var(--primary)", color: "white", paddingTop: "var(--spacing-xl)", paddingBottom: "calc(var(--spacing-xl) + 2rem)" }}>
        <div className="container">
          <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: "1rem", flexWrap: "wrap" }}>
            <div style={{ display: "flex", alignItems: "center", gap: "1.5rem" }}>
              <div style={{ width: "80px", height: "80px", borderRadius: "50%", background: "var(--primary-light)", display: "flex", alignItems: "center", justifyContent: "center", fontSize: "2rem", fontWeight: 700, border: "4px solid rgba(255,255,255,0.2)" }}>
                {inisial || "W"}
              </div>
              <div>
                <h1 style={{ fontSize: "1.5rem", marginBottom: "0.25rem", color: "white" }}>{user.nama}</h1>
                <p style={{ opacity: 0.85, fontSize: "0.875rem" }}>{user.email}</p>
                {user.telepon && <p style={{ opacity: 0.7, fontSize: "0.8rem" }}>{user.telepon}</p>}
              </div>
            </div>
            <LogoutButton className="btn" />
          </div>
        </div>
      </section>

      <section className="container" style={{ marginTop: "-2rem" }}>
        <div style={{ background: "white", borderRadius: "16px", padding: "1.5rem", display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(160px, 1fr))", gap: "1rem", boxShadow: "0 4px 20px rgba(0,0,0,0.05)" }}>
          <div style={{ padding: "1rem" }}>
            <div style={{ color: "var(--text-muted)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Total Laporan</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{laporan.length}</div>
          </div>
          <div style={{ padding: "1rem" }}>
            <div style={{ color: "var(--warning)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Diproses</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{hitung("diproses")}</div>
          </div>
          <div style={{ padding: "1rem" }}>
            <div style={{ color: "var(--success)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Selesai</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{hitung("selesai")}</div>
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "minmax(0, 2fr) minmax(0, 1fr)", gap: "var(--spacing-lg)", marginTop: "var(--spacing-lg)", alignItems: "start" }} className="user-grid">
          <div>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1rem" }}>
              <h2 style={{ fontSize: "1.25rem" }}>Laporan Saya</h2>
              <Link href="/laporan?tab=mine" style={{ fontSize: "0.875rem", color: "var(--primary)", fontWeight: 600 }}>
                Lihat semua
              </Link>
            </div>

            <div style={{ background: "white", borderRadius: "16px", border: "1px solid var(--border)", overflow: "hidden" }}>
              {laporan.length === 0 ? (
                <div style={{ padding: "2.5rem 1.5rem", textAlign: "center", color: "var(--text-muted)" }}>
                  Kamu belum pernah mengirim laporan.
                </div>
              ) : (
                laporan.slice(0, 6).map((item) => {
                  const style = STATUS_STYLE[item.status] ?? STATUS_STYLE.baru;
                  return (
                    <div key={item.id} style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "1rem", padding: "1.25rem 1.5rem", borderBottom: "1px solid var(--border)" }}>
                      <div style={{ minWidth: 0 }}>
                        <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "0.25rem", flexWrap: "wrap" }}>
                          <span style={{ fontSize: "0.7rem", fontWeight: 700, color: style.color, background: style.bg, padding: "0.2rem 0.55rem", borderRadius: "20px" }}>
                            {labelOf(LABEL_STATUS_LAPORAN, item.status)}
                          </span>
                          <span style={{ fontSize: "0.75rem", color: "var(--text-muted)" }}>{item.kode}</span>
                        </div>
                        <h3 style={{ fontSize: "1rem", color: "var(--text-main)", marginBottom: "0.15rem", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{item.judul}</h3>
                        <p style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>
                          {item.kecamatan?.nama ?? "Lokasi tidak disebutkan"} · {item.created_at ? formatTanggal(item.created_at) : ""}
                        </p>
                      </div>
                      <ChevronRight color="var(--text-light)" />
                    </div>
                  );
                })
              )}
            </div>
          </div>

          <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            <div style={{ background: "white", borderRadius: "16px", border: "1px solid var(--border)", padding: "1.5rem" }}>
              <div style={{ color: "var(--primary)", marginBottom: "0.75rem" }}>
                <BarChart3 size={26} />
              </div>
              <h3 style={{ fontSize: "1.1rem", marginBottom: "0.35rem" }}>Pantau aduan kamu</h3>
              <p style={{ fontSize: "0.875rem", color: "var(--text-muted)", marginBottom: "1rem" }}>
                Setiap laporan punya kode pelacakan dan status yang bisa dilihat kapan saja.
              </p>
              <Link href="/laporan" className="btn btn-outline" style={{ width: "100%" }}>
                Buka feed laporan
              </Link>
            </div>

            <div style={{ background: "var(--primary)", color: "white", borderRadius: "16px", padding: "1.5rem" }}>
              <div style={{ marginBottom: "0.75rem" }}>
                <PlusCircle size={26} />
              </div>
              <h3 style={{ fontSize: "1.1rem", marginBottom: "0.35rem", color: "white" }}>Ada masalah di sekitar?</h3>
              <p style={{ fontSize: "0.875rem", opacity: 0.85, marginBottom: "1rem" }}>Kirim laporan baru dengan foto dan titik lokasi.</p>
              <Link href="/laporan/buat" className="btn" style={{ width: "100%", background: "white", color: "var(--primary)" }}>
                Buat laporan
              </Link>
            </div>
          </div>
        </div>
      </section>

      <style
        dangerouslySetInnerHTML={{
          __html: `
        @media (max-width: 768px) {
          .user-grid { grid-template-columns: 1fr !important; }
        }
      `,
        }}
      />
    </div>
  );
}
