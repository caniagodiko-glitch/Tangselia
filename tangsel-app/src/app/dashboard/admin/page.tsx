import { redirect } from "next/navigation";
import Link from "next/link";
import { Briefcase, CheckCircle2, Clock, FileText, Rss, XCircle } from "lucide-react";
import AdminLowonganPanel from "@/components/admin/AdminLowonganPanel";
import BeritaRefresh from "@/components/admin/BeritaRefresh";
import LogoutButton from "@/components/auth/LogoutButton";
import { authedFetch, getCurrentUser } from "@/lib/auth";
import type { BeritaSumber, Lowongan, MetaKurasiLowongan, Paginated } from "@/lib/api";
import { formatTanggal } from "@/lib/format";

export default async function AdminDashboard() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login?next=/dashboard/admin");
  }
  if (user.role !== "admin") {
    redirect("/dashboard/user");
  }

  const [lowonganRes, metaRes, sumberRes] = await Promise.all([
    authedFetch<Paginated<Lowongan>>("/admin/lowongan", { per_page: 50 }),
    authedFetch<{ data: MetaKurasiLowongan }>("/admin/lowongan/meta"),
    authedFetch<{ data: BeritaSumber[] }>("/admin/berita/sumber"),
  ]);

  const lowongan = lowonganRes.data;
  const meta = metaRes.data;
  const sumber = sumberRes.data;
  const hitung = (status: string) => lowongan.filter((item) => item.status === status).length;

  return (
    <div style={{ background: "#f1f5f9", minHeight: "100vh", display: "flex" }}>
      <aside style={{ width: "260px", background: "white", borderRight: "1px solid #e2e8f0", display: "flex", flexDirection: "column", position: "fixed", height: "100vh", zIndex: 10 }}>
        <div style={{ padding: "1.5rem", borderBottom: "1px solid #e2e8f0" }}>
          <div className="brand" style={{ fontSize: "1.25rem" }}>
            TANGSEL<span>LIA</span>
          </div>
          <div style={{ fontSize: "0.75rem", fontWeight: 600, color: "#64748b", marginTop: "0.25rem", letterSpacing: "0.05em" }}>ADMIN PANEL</div>
        </div>
        <div style={{ padding: "1.5rem 1rem", display: "flex", flexDirection: "column", gap: "0.5rem", flex: 1 }}>
          <Link href="/dashboard/admin" className="admin-nav-item active">
            <Briefcase size={18} /> Kurasi Lowongan
          </Link>
          <Link href="/berita" className="admin-nav-item">
            <FileText size={18} /> Berita & Editorial
          </Link>
          <Link href="/kuliner" className="admin-nav-item">
            <Rss size={18} /> Direktori Tempat
          </Link>
        </div>
        <div style={{ padding: "1.5rem", borderTop: "1px solid #e2e8f0", display: "flex", flexDirection: "column", gap: "0.75rem" }}>
          <div style={{ display: "flex", alignItems: "center", gap: "0.75rem" }}>
            <div style={{ width: "36px", height: "36px", borderRadius: "50%", background: "var(--primary)", color: "white", display: "flex", alignItems: "center", justifyContent: "center", fontWeight: 600 }}>
              {user.nama.charAt(0).toUpperCase()}
            </div>
            <div>
              <div style={{ fontSize: "0.875rem", fontWeight: 600, color: "#0f172a" }}>{user.nama}</div>
              <div style={{ fontSize: "0.75rem", color: "#64748b" }}>Administrator</div>
            </div>
          </div>
          <LogoutButton />
        </div>
      </aside>

      <main style={{ marginLeft: "260px", flex: 1, padding: "2rem" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "2rem", flexWrap: "wrap", gap: "1rem" }}>
          <div>
            <h1 style={{ fontSize: "1.75rem", color: "#0f172a", marginBottom: "0.25rem" }}>Moderasi Platform</h1>
            <p style={{ color: "#64748b" }}>Kurasi lowongan kerja dan pantau sumber berita.</p>
          </div>
          <BeritaRefresh />
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(200px, 1fr))", gap: "1.5rem", marginBottom: "2rem" }}>
          <div className="admin-stat-card">
            <div style={{ width: "44px", height: "44px", borderRadius: "12px", background: "#f1f5f9", color: "#475569", display: "flex", alignItems: "center", justifyContent: "center", marginBottom: "0.9rem" }}>
              <Briefcase size={22} />
            </div>
            <div style={{ fontSize: "2rem", fontWeight: 700, color: "#0f172a", lineHeight: 1, marginBottom: "0.4rem" }}>{lowongan.length}</div>
            <div style={{ color: "#64748b", fontSize: "0.85rem", fontWeight: 500 }}>Lowongan terbaru dimuat</div>
          </div>

          <div className="admin-stat-card">
            <div style={{ width: "44px", height: "44px", borderRadius: "12px", background: "#eff6ff", color: "#3b82f6", display: "flex", alignItems: "center", justifyContent: "center", marginBottom: "0.9rem" }}>
              <Clock size={22} />
            </div>
            <div style={{ fontSize: "2rem", fontWeight: 700, color: "#0f172a", lineHeight: 1, marginBottom: "0.4rem" }}>{hitung("baru")}</div>
            <div style={{ color: "#64748b", fontSize: "0.85rem", fontWeight: 500 }}>Menunggu kurasi</div>
          </div>

          <div className="admin-stat-card">
            <div style={{ width: "44px", height: "44px", borderRadius: "12px", background: "#dcfce7", color: "#10b981", display: "flex", alignItems: "center", justifyContent: "center", marginBottom: "0.9rem" }}>
              <CheckCircle2 size={22} />
            </div>
            <div style={{ fontSize: "2rem", fontWeight: 700, color: "#0f172a", lineHeight: 1, marginBottom: "0.4rem" }}>{hitung("tayang")}</div>
            <div style={{ color: "#64748b", fontSize: "0.85rem", fontWeight: 500 }}>Tayang di publik</div>
          </div>

          <div className="admin-stat-card">
            <div style={{ width: "44px", height: "44px", borderRadius: "12px", background: "#fee2e2", color: "#dc2626", display: "flex", alignItems: "center", justifyContent: "center", marginBottom: "0.9rem" }}>
              <XCircle size={22} />
            </div>
            <div style={{ fontSize: "2rem", fontWeight: 700, color: "#0f172a", lineHeight: 1, marginBottom: "0.4rem" }}>{hitung("ditolak")}</div>
            <div style={{ color: "#64748b", fontSize: "0.85rem", fontWeight: 500 }}>Ditolak saat kurasi</div>
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "minmax(0, 2fr) minmax(0, 1fr)", gap: "1.5rem", alignItems: "start" }}>
          <AdminLowonganPanel lowongan={lowongan} meta={meta} />

          <div style={{ background: "white", borderRadius: "16px", border: "1px solid #e2e8f0", padding: "1.5rem" }}>
            <h2 style={{ fontSize: "1.25rem", color: "#0f172a", marginBottom: "0.25rem" }}>Sumber Berita</h2>
            <p style={{ fontSize: "0.8rem", color: "#64748b", marginBottom: "1.25rem" }}>Kesehatan agregasi RSS.</p>

            {sumber.length === 0 ? (
              <p style={{ fontSize: "0.9rem", color: "#64748b" }}>Belum ada sumber terdaftar.</p>
            ) : (
              <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
                {sumber.map((item) => (
                  <div key={item.id} style={{ borderBottom: "1px solid #f1f5f9", paddingBottom: "0.85rem" }}>
                    <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "0.5rem", marginBottom: "0.25rem" }}>
                      <span style={{ fontSize: "0.9rem", fontWeight: 600, color: "#0f172a" }}>{item.nama}</span>
                      <span
                        style={{
                          fontSize: "0.7rem",
                          fontWeight: 700,
                          color: item.aktif ? "#10b981" : "#94a3b8",
                          background: item.aktif ? "#dcfce7" : "#f1f5f9",
                          padding: "0.15rem 0.5rem",
                          borderRadius: "20px",
                        }}
                      >
                        {item.aktif ? "AKTIF" : "NONAKTIF"}
                      </span>
                    </div>
                    <div style={{ fontSize: "0.75rem", color: "#64748b" }}>
                      {item.item_count ?? 0} artikel · terakhir{" "}
                      {item.last_fetched_at ? formatTanggal(item.last_fetched_at) : "belum pernah"}
                    </div>
                    {item.last_error && <div style={{ fontSize: "0.72rem", color: "#dc2626", marginTop: "0.2rem" }}>{item.last_error}</div>}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <style
          dangerouslySetInnerHTML={{
            __html: `
        .admin-nav-item {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          padding: 0.75rem 1rem;
          border-radius: 8px;
          color: #64748b;
          font-weight: 500;
          font-size: 0.875rem;
          transition: all 0.2s;
        }
        .admin-nav-item:hover { background: #f1f5f9; color: #0f172a; }
        .admin-nav-item.active { background: #e6f4f1; color: var(--primary); font-weight: 600; }
        .admin-stat-card {
          background: white;
          border-radius: 16px;
          padding: 1.5rem;
          border: 1px solid #e2e8f0;
          box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        @media (max-width: 1023px) {
          aside { display: none !important; }
          main { margin-left: 0 !important; }
          main > div:last-of-type { grid-template-columns: 1fr !important; }
        }
      `,
          }}
        />
      </main>
    </div>
  );
}
