"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { Camera, MapPin, Signpost } from "lucide-react";
import type { Laporan } from "@/lib/api";
import { LABEL_STATUS_LAPORAN, formatTanggal, labelOf } from "@/lib/format";

const STATUS_STYLE: Record<string, { color: string; bg: string }> = {
  baru: { color: "#475569", bg: "#f1f5f9" },
  diverifikasi: { color: "#3b82f6", bg: "#eff6ff" },
  diproses: { color: "#d97706", bg: "#fef3c7" },
  selesai: { color: "#10b981", bg: "#dcfce7" },
  ditolak: { color: "#dc2626", bg: "#fee2e2" },
};

const FILTERS = ["semua", "baru", "diverifikasi", "diproses", "selesai", "ditolak"] as const;

type Aksi = { endpoint: "verifikasi" | "status"; status: string; label: string; tone: "primary" | "danger" | "warning" | "success" };

function aksiUntuk(status: string): Aksi[] {
  switch (status) {
    case "baru":
      return [
        { endpoint: "verifikasi", status: "diverifikasi", label: "Verifikasi", tone: "primary" },
        { endpoint: "verifikasi", status: "ditolak", label: "Tolak", tone: "danger" },
      ];
    case "diverifikasi":
      return [
        { endpoint: "status", status: "diproses", label: "Proses", tone: "warning" },
        { endpoint: "status", status: "selesai", label: "Selesaikan", tone: "success" },
      ];
    case "diproses":
      return [{ endpoint: "status", status: "selesai", label: "Selesaikan", tone: "success" }];
    default:
      return [];
  }
}

const TONE: Record<Aksi["tone"], { color: string; border: string }> = {
  primary: { color: "#fff", border: "var(--primary)" },
  danger: { color: "#dc2626", border: "#fecaca" },
  warning: { color: "#d97706", border: "#fde68a" },
  success: { color: "#10b981", border: "#bbf7d0" },
};

export default function AdminLaporanPanel({ laporan }: { laporan: Laporan[] }) {
  const router = useRouter();
  const [filter, setFilter] = useState<(typeof FILTERS)[number]>("semua");
  const [busy, setBusy] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [, startTransition] = useTransition();

  const jumlah = FILTERS.reduce<Record<string, number>>(
    (acc, status) => {
      acc[status] = status === "semua" ? laporan.length : laporan.filter((item) => item.status === status).length;
      return acc;
    },
    {},
  );

  const daftar = filter === "semua" ? laporan : laporan.filter((item) => item.status === filter);

  async function ubahStatus(item: Laporan, aksi: Aksi) {
    setBusy(`${item.kode}-${aksi.status}`);
    setError(null);

    try {
      const response = await fetch(`/api/admin/laporan/${item.kode}/${aksi.endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ status: aksi.status }),
      });
      const data = (await response.json().catch(() => ({}))) as { message?: string };

      if (!response.ok) {
        setError(data.message ?? "Gagal mengubah status laporan.");
        return;
      }

      startTransition(() => router.refresh());
    } catch {
      setError("Tidak bisa menghubungi server.");
    } finally {
      setBusy(null);
    }
  }

  return (
    <div style={{ background: "white", borderRadius: "16px", border: "1px solid #e2e8f0", padding: "1.5rem" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: "1rem", flexWrap: "wrap", marginBottom: "1.5rem" }}>
        <div>
          <h2 style={{ fontSize: "1.25rem", color: "#0f172a" }}>Laporan Warga</h2>
          <p style={{ fontSize: "0.8rem", color: "#64748b" }}>Menampilkan {laporan.length} laporan terbaru.</p>
        </div>
        <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
          {FILTERS.map((status) => (
            <button
              key={status}
              type="button"
              onClick={() => setFilter(status)}
              style={{
                padding: "0.4rem 0.85rem",
                borderRadius: "999px",
                fontSize: "0.8rem",
                fontWeight: 600,
                cursor: "pointer",
                border: "1px solid #e2e8f0",
                background: filter === status ? "#0f172a" : "white",
                color: filter === status ? "white" : "#64748b",
              }}
            >
              {status === "semua" ? "Semua" : labelOf(LABEL_STATUS_LAPORAN, status)} ({jumlah[status] ?? 0})
            </button>
          ))}
        </div>
      </div>

      {error && (
        <p role="alert" style={{ color: "#dc2626", background: "#fef2f2", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem", marginBottom: "1rem" }}>
          {error}
        </p>
      )}

      {daftar.length === 0 ? (
        <p style={{ color: "#64748b", fontSize: "0.95rem", padding: "2rem 0", textAlign: "center" }}>Tidak ada laporan pada filter ini.</p>
      ) : (
        <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
          {daftar.map((item) => {
            const style = STATUS_STYLE[item.status] ?? STATUS_STYLE.baru;
            const aksi = aksiUntuk(item.status);

            return (
              <div key={item.id} style={{ border: "1px solid #e2e8f0", borderRadius: "12px", padding: "1rem" }}>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "0.75rem", flexWrap: "wrap", marginBottom: "0.5rem" }}>
                  <span style={{ fontSize: "0.75rem", fontWeight: 700, color: style.color, background: style.bg, padding: "0.25rem 0.75rem", borderRadius: "20px" }}>
                    {labelOf(LABEL_STATUS_LAPORAN, item.status)}
                  </span>
                  <span style={{ fontSize: "0.75rem", color: "#64748b", fontWeight: 600 }}>{item.kode}</span>
                </div>

                <h3 style={{ fontSize: "1.05rem", color: "#0f172a", marginBottom: "0.35rem" }}>{item.judul}</h3>
                <p style={{ fontSize: "0.875rem", color: "#64748b", lineHeight: 1.5, marginBottom: "0.75rem" }}>{item.deskripsi}</p>

                <div style={{ display: "flex", flexWrap: "wrap", gap: "1rem", fontSize: "0.78rem", color: "#94a3b8", marginBottom: aksi.length > 0 ? "0.85rem" : 0 }}>
                  {item.kategori && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <Signpost size={13} /> {item.kategori.nama}
                    </span>
                  )}
                  {item.kecamatan && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <MapPin size={13} /> {item.kecamatan.nama}
                    </span>
                  )}
                  {item.foto.length > 0 && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <Camera size={13} /> {item.foto.length} foto
                    </span>
                  )}
                  <span>{item.created_at ? formatTanggal(item.created_at) : ""}</span>
                  {item.pelapor && <span>Oleh {item.pelapor.nama}</span>}
                </div>

                {aksi.length > 0 && (
                  <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
                    {aksi.map((a) => {
                      const tone = TONE[a.tone];
                      const sedang = busy === `${item.kode}-${a.status}`;
                      return (
                        <button
                          key={a.status}
                          type="button"
                          disabled={busy !== null}
                          onClick={() => ubahStatus(item, a)}
                          style={{
                            padding: "0.45rem 1rem",
                            borderRadius: "8px",
                            fontSize: "0.82rem",
                            fontWeight: 600,
                            cursor: busy ? "default" : "pointer",
                            border: `1px solid ${tone.border}`,
                            background: a.tone === "primary" ? "var(--primary)" : "white",
                            color: tone.color,
                            opacity: busy && !sedang ? 0.6 : 1,
                          }}
                        >
                          {sedang ? "Memproses..." : a.label}
                        </button>
                      );
                    })}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
