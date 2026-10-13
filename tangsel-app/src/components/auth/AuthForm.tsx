"use client";

import { useState, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { Lock, Mail, Phone, User } from "lucide-react";

type Mode = "login" | "register";

export default function AuthForm({ mode, next }: { mode: Mode; next: string }) {
  const router = useRouter();
  const isRegister = mode === "register";

  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [values, setValues] = useState({
    nama: "",
    email: "",
    password: "",
    password_confirmation: "",
    telepon: "",
  });

  function update(field: keyof typeof values, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    setError(null);

    const endpoint = isRegister ? "/api/auth/register" : "/api/auth/login";
    const payload = isRegister
      ? {
          nama: values.nama,
          email: values.email,
          password: values.password,
          password_confirmation: values.password_confirmation,
          telepon: values.telepon || undefined,
        }
      : { email: values.email, password: values.password };

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = (await response.json().catch(() => ({}))) as { message?: string };

      if (!response.ok) {
        setError(data.message ?? "Terjadi kesalahan. Coba lagi.");
        setPending(false);
        return;
      }

      router.replace(next);
      router.refresh();
    } catch {
      setError("Tidak bisa menghubungi server. Pastikan koneksi aktif.");
      setPending(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="auth-card">
      <div className="auth-field">
        <label className="form-label" htmlFor="email">
          <Mail size={15} /> Email
        </label>
        <input
          id="email"
          type="email"
          required
          autoComplete="email"
          className="form-control"
          value={values.email}
          onChange={(event) => update("email", event.target.value)}
          placeholder="nama@email.com"
        />
      </div>

      {isRegister && (
        <div className="auth-field">
          <label className="form-label" htmlFor="nama">
            <User size={15} /> Nama lengkap
          </label>
          <input
            id="nama"
            type="text"
            required
            autoComplete="name"
            className="form-control"
            value={values.nama}
            onChange={(event) => update("nama", event.target.value)}
            placeholder="Nama kamu"
          />
        </div>
      )}

      {isRegister && (
        <div className="auth-field">
          <label className="form-label" htmlFor="telepon">
            <Phone size={15} /> Nomor telepon (opsional)
          </label>
          <input
            id="telepon"
            type="tel"
            autoComplete="tel"
            className="form-control"
            value={values.telepon}
            onChange={(event) => update("telepon", event.target.value)}
            placeholder="08xxxxxxxxxx"
          />
        </div>
      )}

      <div className="auth-field">
        <label className="form-label" htmlFor="password">
          <Lock size={15} /> Kata sandi
        </label>
        <input
          id="password"
          type="password"
          required
          minLength={8}
          autoComplete={isRegister ? "new-password" : "current-password"}
          className="form-control"
          value={values.password}
          onChange={(event) => update("password", event.target.value)}
          placeholder="Minimal 8 karakter"
        />
      </div>

      {isRegister && (
        <div className="auth-field">
          <label className="form-label" htmlFor="password_confirmation">
            <Lock size={15} /> Ulangi kata sandi
          </label>
          <input
            id="password_confirmation"
            type="password"
            required
            minLength={8}
            autoComplete="new-password"
            className="form-control"
            value={values.password_confirmation}
            onChange={(event) => update("password_confirmation", event.target.value)}
            placeholder="Ulangi kata sandi"
          />
        </div>
      )}

      {error && (
        <p role="alert" style={{ color: "var(--error)", background: "#fef2f2", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem" }}>
          {error}
        </p>
      )}

      <button type="submit" className="btn btn-primary" disabled={pending} style={{ width: "100%", opacity: pending ? 0.7 : 1 }}>
        {pending ? "Memproses..." : isRegister ? "Daftar" : "Masuk"}
      </button>

      <p style={{ textAlign: "center", fontSize: "0.9rem", color: "var(--text-muted)" }}>
        {isRegister ? "Sudah punya akun? " : "Belum punya akun? "}
        <Link
          href={`/login?mode=${isRegister ? "login" : "register"}&next=${encodeURIComponent(next)}`}
          style={{ color: "var(--primary)", fontWeight: 600 }}
        >
          {isRegister ? "Masuk" : "Daftar"}
        </Link>
      </p>
    </form>
  );
}
