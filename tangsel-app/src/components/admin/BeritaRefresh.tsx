"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { RefreshCw } from "lucide-react";

type Hasil = {
  sumber?: number;
  diambil?: number;
  baru?: number;
  diperbarui?: number;
  gagal?: number;
};

export default function BeritaRefresh() {
  const router = useRouter();
  const [busy, setBusy] = useState(false);
  const [hasil, setHasil] = useState<Hasil | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [, startTransition] = useTransition();

  async function tarik() {
    setBusy(true);
    setError(null);
    setHasil(null);

    try {
      const response = await fetch("/api/admin/berita/refresh", { method: "POST" });
      const data = (await response.json().catch(() => ({}))) as { data?: Hasil; message?: string };

      if (!response.ok) {
        setError(data.message ?? "Gagal menarik berita.");
        return;
      }

      setHasil(data.data ?? {});
      startTransition(() => router.refresh());
    } catch {
      setError("Tidak bisa menghubungi server.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div>
      <button
        type="button"
        className="btn btn-outline"
        onClick={tarik}
        disabled={busy}
        style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem", background: "white", opacity: busy ? 0.7 : 1 }}
      >
        <RefreshCw size={16} className={busy ? "spin" : undefined} /> {busy ? "Menarik..." : "Tarik Berita Sekarang"}
      </button>

      {error && <p style={{ color: "#dc2626", fontSize: "0.85rem", marginTop: "0.75rem" }}>{error}</p>}

      {hasil && (
        <p style={{ color: "#0f172a", fontSize: "0.85rem", marginTop: "0.75rem" }}>
          {hasil.sumber ?? 0} sumber diproses · {hasil.baru ?? 0} baru · {hasil.diperbarui ?? 0} diperbarui · {hasil.gagal ?? 0} gagal
        </p>
      )}

      <style
        dangerouslySetInnerHTML={{
          __html: `
        @keyframes spin { to { transform: rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }
      `,
        }}
      />
    </div>
  );
}
