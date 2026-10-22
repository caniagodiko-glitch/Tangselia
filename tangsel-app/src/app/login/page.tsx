import { redirect } from "next/navigation";
import AuthForm from "@/components/auth/AuthForm";
import { getCurrentUser } from "@/lib/auth";

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<{ next?: string; mode?: string }>;
}) {
  const sp = await searchParams;
  const next = typeof sp.next === "string" && sp.next.startsWith("/") ? sp.next : "/dashboard/user";
  const mode = sp.mode === "register" ? "register" : "login";

  const user = await getCurrentUser();
  if (user) {
    redirect(user.role === "admin" ? "/dashboard/admin" : next);
  }

  return (
    <div style={{ background: "var(--surface-hover)", minHeight: "100vh", display: "flex", alignItems: "center", justifyContent: "center", padding: "var(--spacing-lg) var(--spacing-md)" }}>
      <div style={{ width: "100%", maxWidth: "440px", background: "#fff", border: "1px solid var(--border)", borderRadius: "24px", padding: "var(--spacing-lg)", boxShadow: "0 20px 50px rgba(0,0,0,0.06)" }}>
        <div style={{ textAlign: "center", marginBottom: "1.5rem" }}>
          <div className="brand" style={{ justifyContent: "center", fontSize: "1.5rem" }}>
            TANGSEL<span>LIA</span>
          </div>
          <h1 style={{ fontSize: "1.5rem", marginTop: "0.75rem", marginBottom: "0.35rem" }}>
            {mode === "register" ? "Buat akun warga" : "Selamat datang kembali"}
          </h1>
          <p style={{ color: "var(--text-muted)", fontSize: "0.95rem" }}>
            {mode === "register"
              ? "Daftar untuk mendapat informasi lowongan kerja dan magang di Tangsel."
              : "Masuk untuk melihat lowongan terbaru atau mengelola konten."}
          </p>
        </div>

        <AuthForm mode={mode} next={next} />
      </div>

      <style
        dangerouslySetInnerHTML={{
          __html: `
        .auth-card { display: flex; flex-direction: column; gap: 1rem; }
        .auth-field { display: flex; flex-direction: column; }
        .auth-field .form-label { display: flex; align-items: center; gap: 0.4rem; }
      `,
        }}
      />
    </div>
  );
}
