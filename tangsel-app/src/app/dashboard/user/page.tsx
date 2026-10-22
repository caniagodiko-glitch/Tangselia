import { redirect } from "next/navigation";
import Link from "next/link";
import { Briefcase, Building2, ChevronRight, Search } from "lucide-react";
import LogoutButton from "@/components/auth/LogoutButton";
import { getCurrentUser } from "@/lib/auth";
import { getLowongan, getLowonganMeta } from "@/lib/api";
import { LABEL_JENIS_LOWONGAN, formatTanggal } from "@/lib/format";

export default async function UserDashboard() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login?next=/dashboard/user");
  }

  const [lowonganRes, metaRes] = await Promise.all([
    getLowongan({ per_page: 6 }),
    getLowonganMeta(),
  ]);

  const lowongan = lowonganRes.data;
  const meta = metaRes.data;
  const jumlahJenis = (nilai: string) =>
    meta.jenis.find((item) => item.value === nilai)?.jumlah ?? 0;
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
            <div style={{ color: "var(--text-muted)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Total Lowongan</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{lowongan.length}</div>
          </div>
          <div style={{ padding: "1rem" }}>
            <div style={{ color: "var(--primary)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Magang</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{jumlahJenis("magang")}</div>
          </div>
          <div style={{ padding: "1rem" }}>
            <div style={{ color: "var(--success)", fontSize: "0.8rem", fontWeight: 600, marginBottom: "0.5rem", textTransform: "uppercase" }}>Karier</div>
            <div style={{ fontSize: "2.25rem", fontWeight: 700, color: "var(--text-main)", lineHeight: 1 }}>{jumlahJenis("karir")}</div>
          </div>
        </div>

        <div style={{ display: "grid", gridTemplateColumns: "minmax(0, 2fr) minmax(0, 1fr)", gap: "var(--spacing-lg)", marginTop: "var(--spacing-lg)", alignItems: "start" }} className="user-grid">
          <div>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: "1rem" }}>
              <h2 style={{ fontSize: "1.25rem" }}>Lowongan Terbaru</h2>
              <Link href="/lowongan" style={{ fontSize: "0.875rem", color: "var(--primary)", fontWeight: 600 }}>
                Lihat semua
              </Link>
            </div>

            <div style={{ background: "white", borderRadius: "16px", border: "1px solid var(--border)", overflow: "hidden" }}>
              {lowongan.length === 0 ? (
                <div style={{ padding: "2.5rem 1.5rem", textAlign: "center", color: "var(--text-muted)" }}>
                  Belum ada lowongan yang tayang saat ini.
                </div>
              ) : (
                lowongan.map((item) => (
                  <Link
                    key={item.id}
                    href={`/lowongan/${item.slug}`}
                    style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "1rem", padding: "1.25rem 1.5rem", borderBottom: "1px solid var(--border)", color: "inherit" }}
                  >
                    <div style={{ minWidth: 0 }}>
                      <div style={{ display: "flex", alignItems: "center", gap: "0.5rem", marginBottom: "0.25rem", flexWrap: "wrap" }}>
                        <span style={{ fontSize: "0.7rem", fontWeight: 700, color: "var(--primary)", background: "#e6f4f1", padding: "0.2rem 0.55rem", borderRadius: "20px" }}>
                          {LABEL_JENIS_LOWONGAN[item.jenis] ?? item.jenis}
                        </span>
                        <span style={{ fontSize: "0.75rem", color: "var(--text-muted)" }}>{item.kode}</span>
                      </div>
                      <h3 style={{ fontSize: "1rem", color: "var(--text-main)", marginBottom: "0.15rem", overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>
                        {item.posisi ?? item.judul}
                      </h3>
                      <p style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>
                        {item.perusahaan ?? item.sumber ?? "Perusahaan tidak disebutkan"}
                        {item.published_at ? ` • ${formatTanggal(item.published_at)}` : ""}
                      </p>
                    </div>
                    <ChevronRight color="var(--text-light)" />
                  </Link>
                ))
              )}
            </div>
          </div>

          <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
            <div style={{ background: "white", borderRadius: "16px", border: "1px solid var(--border)", padding: "1.5rem" }}>
              <div style={{ color: "var(--primary)", marginBottom: "0.75rem" }}>
                <Search size={26} />
              </div>
              <h3 style={{ fontSize: "1.1rem", marginBottom: "0.35rem" }}>Cari lowongan sesuai bidang</h3>
              <p style={{ fontSize: "0.875rem", color: "var(--text-muted)", marginBottom: "1rem" }}>
                Saring lowongan berdasarkan jenis, bidang pekerjaan, dan kecamatan tempat lowongan dipublikasikan.
              </p>
              <Link href="/lowongan" className="btn btn-outline" style={{ width: "100%" }}>
                Buka semua lowongan
              </Link>
            </div>

            <div style={{ background: "var(--primary)", color: "white", borderRadius: "16px", padding: "1.5rem" }}>
              <div style={{ marginBottom: "0.75rem" }}>
                <Briefcase size={26} />
              </div>
              <h3 style={{ fontSize: "1.1rem", marginBottom: "0.35rem", color: "white" }}>Mencari pekerjaan magang?</h3>
              <p style={{ fontSize: "0.875rem", opacity: 0.85, marginBottom: "1rem" }}>
                Lihat lowongan magang yang sudah dikurasi untuk wilayah Tangerang Selatan.
              </p>
              <Link href="/lowongan?jenis=magang" className="btn" style={{ width: "100%", background: "white", color: "var(--primary)" }}>
                Lihat lowongan magang
              </Link>
            </div>

            <div style={{ background: "white", borderRadius: "16px", border: "1px solid var(--border)", padding: "1.5rem" }}>
              <div style={{ color: "var(--text-muted)", marginBottom: "0.75rem" }}>
                <Building2 size={26} />
              </div>
              <h3 style={{ fontSize: "1.1rem", marginBottom: "0.35rem" }}>Perusahaan membuka lowongan</h3>
              <p style={{ fontSize: "0.875rem", color: "var(--text-muted)", marginBottom: "1rem" }}>
                Informasi lowongan dihimpun dari job board dan pemberita lokal, lalu diperiksa admin sebelum tayang.
              </p>
              <Link href="/lowongan" className="btn btn-outline" style={{ width: "100%" }}>
                Telusuri lowongan
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
