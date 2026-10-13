import type { Metadata } from "next";
import "./globals.css";
import { Home, Compass, FileText, User, AlertCircle, PlusCircle } from "lucide-react";
import Link from "next/link";
import { getCurrentUser } from "@/lib/auth";

export const metadata: Metadata = {
  title: "TANGSEL LIA - Satu tempat untuk menemukan informasi dan layanan Tangsel.",
  description: "Platform informasi lokal dan layanan publik premium untuk warga Tangerang Selatan.",
};

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const user = await getCurrentUser();
  const dashboardHref = user?.role === "admin" ? "/dashboard/admin" : "/dashboard/user";

  return (
    <html lang="id">
      <body>
        <header className="app-header">
          <div className="container header-inner">
            <Link href="/" className="brand">
              TANGSEL<span>LIA</span>
            </Link>

            <nav className="desktop-nav">
              <ul>
                <li><Link href="/">Beranda</Link></li>
                <li><Link href="/kuliner">Jelajah</Link></li>
                <li><Link href="/olahraga">Olahraga</Link></li>
                <li><Link href="/kesehatan">Kesehatan</Link></li>
                <li><Link href="/berita">Berita</Link></li>
                <li><Link href="/laporan">Laporan Warga</Link></li>
              </ul>
            </nav>

            <div className="desktop-nav">
              <div style={{ display: "flex", alignItems: "center", gap: "0.75rem" }}>
                <Link href="/laporan/buat" className="btn btn-accent" style={{ display: "flex", gap: "0.5rem" }}>
                  <AlertCircle size={18} />
                  Buat Laporan
                </Link>
                {user ? (
                  <Link href={dashboardHref} className="btn btn-outline" style={{ display: "flex", gap: "0.5rem" }}>
                    <User size={18} />
                    {user.nama.split(" ")[0]}
                  </Link>
                ) : (
                  <Link href="/login" className="btn btn-outline" style={{ display: "flex", gap: "0.5rem" }}>
                    <User size={18} />
                    Masuk
                  </Link>
                )}
              </div>
            </div>
          </div>
        </header>

        <main>
          {children}
        </main>

        <nav className="mobile-nav">
          <Link href="/" className="mobile-nav-item active">
            <Home size={24} />
            <span>Beranda</span>
          </Link>
          <Link href="/kuliner" className="mobile-nav-item">
            <Compass size={24} />
            <span>Jelajah</span>
          </Link>
          <Link href="/berita" className="mobile-nav-item">
            <FileText size={24} />
            <span>Berita</span>
          </Link>
          <Link href="/laporan/buat" className="mobile-nav-item">
            <PlusCircle size={24} />
            <span>Lapor</span>
          </Link>
          <Link href={user ? dashboardHref : "/login"} className="mobile-nav-item">
            <User size={24} />
            <span>{user ? "Profil" : "Masuk"}</span>
          </Link>
        </nav>

        <footer className="app-footer">
          <div className="container footer-grid">
            <div className="footer-col" style={{ gridColumn: 'span 2' }}>
              <Link href="/" className="brand" style={{ color: 'white', marginBottom: '1rem', display: 'inline-block' }}>
                TANGSEL<span style={{ color: 'var(--accent)' }}>LIA</span>
              </Link>
              <p style={{ color: 'var(--text-light)', maxWidth: '300px' }}>
                Satu tempat untuk menemukan informasi dan layanan Tangsel. Platform digital modern untuk warga Tangerang Selatan.
              </p>
            </div>
            <div className="footer-col">
              <h4>Layanan</h4>
              <ul>
                <li><Link href="/kuliner">Kuliner</Link></li>
                <li><Link href="/olahraga">Tempat Olahraga</Link></li>
                <li><Link href="/kesehatan">Layanan Kesehatan</Link></li>
                <li><Link href="/laporan">Laporan Warga</Link></li>
              </ul>
            </div>
            <div className="footer-col">
              <h4>Akun</h4>
              <ul>
                <li><Link href="/login">Masuk</Link></li>
                <li><Link href="/login?mode=register">Daftar</Link></li>
                <li><Link href={dashboardHref}>Dashboard</Link></li>
                <li><Link href="/dashboard/admin">Admin</Link></li>
              </ul>
            </div>
          </div>
          <div className="container" style={{ marginTop: '3rem', paddingTop: '2rem', borderTop: '1px solid rgba(255,255,255,0.1)', textAlign: 'center', color: 'var(--text-light)', fontSize: '0.875rem' }}>
            &copy; {new Date().getFullYear()} TANGSEL LIA. Dirancang untuk Tangerang Selatan.
          </div>
        </footer>
      </body>
    </html>
  );
}
