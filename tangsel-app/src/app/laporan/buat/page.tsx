import { redirect } from "next/navigation";
import Link from "next/link";
import { AlertCircle, ArrowLeft } from "lucide-react";
import LaporanForm from "@/components/laporan/LaporanForm";
import { getLaporanMeta } from "@/lib/api";
import { getCurrentUser } from "@/lib/auth";

export default async function BuatLaporan() {
  const user = await getCurrentUser();

  if (!user) {
    redirect("/login?next=/laporan/buat");
  }

  const meta = (await getLaporanMeta()).data;

  return (
    <div style={{ background: "#fff", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <div style={{ borderBottom: "1px solid var(--border)", position: "sticky", top: "72px", background: "white", zIndex: 10 }}>
        <div className="container" style={{ padding: "1rem var(--spacing-lg)", display: "flex", alignItems: "center", gap: "1rem" }}>
          <Link
            href="/laporan"
            style={{ width: "40px", height: "40px", borderRadius: "50%", background: "var(--surface-hover)", display: "flex", alignItems: "center", justifyContent: "center", color: "var(--text-main)" }}
          >
            <ArrowLeft size={20} />
          </Link>
          <div>
            <h1 style={{ fontSize: "1.25rem", margin: 0 }}>Buat Laporan Baru</h1>
            <p style={{ fontSize: "0.8rem", color: "var(--text-muted)", margin: 0 }}>Masuk sebagai {user.nama}</p>
          </div>
        </div>
      </div>

      <section className="container" style={{ maxWidth: "700px", margin: "0 auto", paddingTop: "var(--spacing-xl)" }}>
        <LaporanForm kategori={meta.kategori} kecamatan={meta.kecamatan} />
        <p style={{ textAlign: "center", fontSize: "0.875rem", color: "var(--text-muted)", marginTop: "1rem", display: "flex", alignItems: "center", justifyContent: "center", gap: "0.5rem" }}>
          <AlertCircle size={14} /> Laporan palsu atau tidak relevan dapat dikenakan sanksi.
        </p>
      </section>
    </div>
  );
}
