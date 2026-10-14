"use client";

import { useRef, useState, type ChangeEvent, type FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { Camera, CheckCircle2, Loader2, Star, X } from "lucide-react";
import type { DirektoriFitur } from "@/lib/api";

type Props = {
  fitur: DirektoriFitur;
  slug: string;
  sudahMasuk: boolean;
};

/**
 * Form ulasan warga: rating bintang, komentar, maksimal 3 foto.
 *
 * Bila belum masuk, tampilkan ajakan login. Pengiriman lewat proxy BFF
 * `/api/ulasan` supaya token Sanctum tetap di cookie httpOnly.
 */
export default function UlasanForm({ fitur, slug, sudahMasuk }: Props) {
  const router = useRouter();
  const [rating, setRating] = useState(0);
  const [komentar, setKomentar] = useState("");
  const [foto, setFoto] = useState<File[]>([]);
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [sukses, setSukses] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);

  function pilihFoto(event: ChangeEvent<HTMLInputElement>) {
    setFoto(Array.from(event.target.files ?? []).slice(0, 3));
  }

  async function kirim(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (rating < 1) {
      setError("Pilih rating bintang dulu ya.");
      return;
    }
    if (komentar.trim().length < 5) {
      setError("Komentar minimal 5 karakter.");
      return;
    }

    setPending(true);

    const form = new FormData();
    form.set("fitur", fitur);
    form.set("slug", slug);
    form.set("rating", String(rating));
    form.set("komentar", komentar);
    foto.forEach((file) => form.append("foto[]", file));

    try {
      const response = await fetch("/api/ulasan", { method: "POST", body: form });
      const data = (await response.json().catch(() => ({}))) as { message?: string };

      if (!response.ok) {
        setError(data.message ?? "Gagal mengirim ulasan. Periksa kembali isianmu.");
        setPending(false);
        return;
      }

      setSukses(true);
      setPending(false);
      setRating(0);
      setKomentar("");
      setFoto([]);
      router.refresh();
    } catch {
      setError("Tidak bisa menghubungi server. Pastikan koneksi aktif.");
      setPending(false);
    }
  }

  if (!sudahMasuk) {
    return (
      <div
        style={{
          border: "1px dashed var(--border)",
          borderRadius: "16px",
          padding: "1.5rem",
          textAlign: "center",
          background: "var(--surface-hover)",
        }}
      >
        <p style={{ color: "var(--text-muted)", marginBottom: "1rem" }}>
          Masuk dulu untuk menulis ulasan dan memberi rating tempat ini.
        </p>
        <Link href="/login" className="btn btn-primary">
          Masuk / Daftar
        </Link>
      </div>
    );
  }

  if (sukses) {
    return (
      <div
        style={{
          display: "flex",
          alignItems: "center",
          gap: "0.75rem",
          border: "1px solid #bbf7d0",
          background: "#f0fdf4",
          color: "#15803d",
          borderRadius: "16px",
          padding: "1rem 1.25rem",
        }}
      >
        <CheckCircle2 size={22} />
        <span style={{ fontWeight: 600 }}>Terima kasih! Ulasanmu sudah tayang.</span>
      </div>
    );
  }

  return (
    <form
      onSubmit={kirim}
      style={{
        border: "1px solid var(--border)",
        borderRadius: "16px",
        padding: "1.25rem",
        background: "#fff",
        display: "flex",
        flexDirection: "column",
        gap: "1rem",
      }}
    >
      <div>
        <div style={{ fontWeight: 700, marginBottom: "0.4rem" }}>Beri rating</div>
        <div style={{ display: "flex", gap: "0.25rem" }}>
          {[1, 2, 3, 4, 5].map((bintang) => (
            <button
              key={bintang}
              type="button"
              onClick={() => setRating(bintang)}
              aria-label={`${bintang} bintang`}
              style={{ background: "none", border: "none", cursor: "pointer", padding: 0, lineHeight: 0 }}
            >
              <Star
                size={28}
                fill={bintang <= rating ? "currentColor" : "none"}
                color={bintang <= rating ? "#FBBF24" : "var(--border)"}
              />
            </button>
          ))}
        </div>
      </div>

      <div className="form-group">
        <label className="form-label" htmlFor="ulasan-komentar">
          Komentar
        </label>
        <textarea
          id="ulasan-komentar"
          required
          minLength={5}
          maxLength={2000}
          className="form-control"
          value={komentar}
          onChange={(event) => setKomentar(event.target.value)}
          placeholder="Ceritakan pengalamanmu di tempat ini..."
          style={{ minHeight: "110px" }}
        />
      </div>

      <div>
        <label className="form-label">Foto (opsional, maks. 3)</label>
        <input ref={fileRef} type="file" accept="image/*" multiple hidden onChange={pilihFoto} />
        <button
          type="button"
          onClick={() => fileRef.current?.click()}
          style={{
            width: "100%",
            border: "2px dashed var(--border)",
            borderRadius: "12px",
            minHeight: "72px",
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            gap: "0.5rem",
            color: "var(--text-muted)",
            cursor: "pointer",
            background: "var(--surface-hover)",
          }}
        >
          <Camera size={20} />
          <span style={{ fontWeight: 500 }}>
            {foto.length > 0 ? `${foto.length} foto dipilih` : "Ambil / pilih foto"}
          </span>
        </button>
        {foto.length > 0 && (
          <ul style={{ listStyle: "none", marginTop: "0.5rem", display: "flex", flexDirection: "column", gap: "0.3rem" }}>
            {foto.map((file, index) => (
              <li
                key={`${file.name}-${index}`}
                style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: "0.5rem", fontSize: "0.85rem", color: "var(--text-muted)" }}
              >
                <span style={{ overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{file.name}</span>
                <button
                  type="button"
                  onClick={() => setFoto((current) => current.filter((_, i) => i !== index))}
                  style={{ border: "none", background: "none", cursor: "pointer", color: "var(--error)" }}
                  aria-label="Hapus foto"
                >
                  <X size={14} />
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>

      {error && (
        <p role="alert" style={{ color: "var(--error)", background: "#fef2f2", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem" }}>
          {error}
        </p>
      )}

      <button type="submit" className="btn btn-primary" disabled={pending} style={{ gap: "0.5rem", opacity: pending ? 0.7 : 1 }}>
        {pending ? <Loader2 size={18} /> : <Star size={18} />}
        {pending ? "Mengirim..." : "Kirim Ulasan"}
      </button>
    </form>
  );
}
