"use client";

import { useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { Building2, ExternalLink, MapPin, Pencil, Save, Tag, Trash2 } from "lucide-react";
import type { Lowongan, MetaKurasiLowongan } from "@/lib/api";
import { LABEL_JENIS_LOWONGAN, LABEL_STATUS_LOWONGAN, formatTanggal, labelOf } from "@/lib/format";

const STATUS_STYLE: Record<string, { color: string; bg: string }> = {
  baru: { color: "#475569", bg: "#f1f5f9" },
  tayang: { color: "#10b981", bg: "#dcfce7" },
  ditolak: { color: "#dc2626", bg: "#fee2e2" },
  kedaluwarsa: { color: "#d97706", bg: "#fef3c7" },
};

const FILTERS = ["semua", "baru", "tayang", "ditolak", "kedaluwarsa"] as const;

type Filter = (typeof FILTERS)[number];

type FormState = {
  posisi: string;
  perusahaan: string;
  jenis: Lowongan["jenis"];
  deskripsi: string;
  kualifikasi: string;
  cara_apply: string;
  kategori_id: string;
  kecamatan_id: string;
};

function formAwal(item: Lowongan): FormState {
  return {
    posisi: item.posisi ?? "",
    perusahaan: item.perusahaan ?? "",
    jenis: item.jenis,
    deskripsi: item.deskripsi ?? "",
    kualifikasi: item.kualifikasi ?? "",
    cara_apply: item.cara_apply ?? "",
    kategori_id: item.kategori ? String(item.kategori.id) : "",
    kecamatan_id: item.kecamatan ? String(item.kecamatan.id) : "",
  };
}

export default function AdminLowonganPanel({
  lowongan,
  meta,
}: {
  lowongan: Lowongan[];
  meta: MetaKurasiLowongan;
}) {
  const router = useRouter();
  const [filter, setFilter] = useState<Filter>("semua");
  const [busy, setBusy] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [sukses, setSukses] = useState<string | null>(null);
  const [editId, setEditId] = useState<number | null>(null);
  const [form, setForm] = useState<FormState | null>(null);
  const [, startTransition] = useTransition();

  const jumlah = FILTERS.reduce<Record<string, number>>(
    (acc, status) => {
      acc[status] =
        status === "semua" ? lowongan.length : lowongan.filter((item) => item.status === status).length;
      return acc;
    },
    {},
  );

  const daftar = filter === "semua" ? lowongan : lowongan.filter((item) => item.status === filter);

  async function kirim(
    item: Lowongan,
    body: Record<string, unknown>,
    pesan: string,
  ): Promise<void> {
    setBusy(`item-${item.id}`);
    setError(null);
    setSukses(null);

    try {
      const response = await fetch(`/api/admin/lowongan/${item.id}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = (await response.json().catch(() => ({}))) as { message?: string };

      if (!response.ok) {
        setError(data.message ?? "Gagal menyimpan perubahan lowongan.");
        return;
      }

      setSukses(pesan);
      setEditId(null);
      setForm(null);
      startTransition(() => router.refresh());
    } catch {
      setError("Tidak bisa menghubungi server.");
    } finally {
      setBusy(null);
    }
  }

  async function hapus(item: Lowongan): Promise<void> {
    if (!window.confirm(`Hapus lowongan ${item.kode}? Tindakan ini tidak dapat dibatalkan.`)) {
      return;
    }

    setBusy(`item-${item.id}`);
    setError(null);
    setSukses(null);

    try {
      const response = await fetch(`/api/admin/lowongan/${item.id}`, { method: "DELETE" });
      const data = (await response.json().catch(() => ({}))) as { message?: string };

      if (!response.ok) {
        setError(data.message ?? "Gagal menghapus lowongan.");
        return;
      }

      setSukses(`Lowongan ${item.kode} dihapus.`);
      startTransition(() => router.refresh());
    } catch {
      setError("Tidak bisa menghubungi server.");
    } finally {
      setBusy(null);
    }
  }

  function bukaEdit(item: Lowongan): void {
    setEditId(item.id);
    setForm(formAwal(item));
    setError(null);
    setSukses(null);
  }

  return (
    <div style={{ background: "white", borderRadius: "16px", border: "1px solid #e2e8f0", padding: "1.5rem" }}>
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-start", gap: "1rem", flexWrap: "wrap", marginBottom: "1.5rem" }}>
        <div>
          <h2 style={{ fontSize: "1.25rem", color: "#0f172a" }}>Kurasi Lowongan</h2>
          <p style={{ fontSize: "0.8rem", color: "#64748b" }}>
            Menampilkan {lowongan.length} lowongan terbaru dari agregator RSS.
          </p>
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
              {status === "semua" ? "Semua" : labelOf(LABEL_STATUS_LOWONGAN, status)} ({jumlah[status] ?? 0})
            </button>
          ))}
        </div>
      </div>

      {error && (
        <p role="alert" style={{ color: "#dc2626", background: "#fef2f2", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem", marginBottom: "1rem" }}>
          {error}
        </p>
      )}

      {sukses && (
        <p role="status" style={{ color: "#047857", background: "#ecfdf5", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem", marginBottom: "1rem" }}>
          {sukses}
        </p>
      )}

      {daftar.length === 0 ? (
        <p style={{ color: "#64748b", fontSize: "0.95rem", padding: "2rem 0", textAlign: "center" }}>
          Tidak ada lowongan pada filter ini.
        </p>
      ) : (
        <div style={{ display: "flex", flexDirection: "column", gap: "1rem" }}>
          {daftar.map((item) => {
            const style = STATUS_STYLE[item.status] ?? STATUS_STYLE.baru;
            const sedang = busy === `item-${item.id}`;
            const sedangEdit = editId === item.id;

            return (
              <div key={item.id} style={{ border: "1px solid #e2e8f0", borderRadius: "12px", padding: "1rem" }}>
                <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", gap: "0.75rem", flexWrap: "wrap", marginBottom: "0.5rem" }}>
                  <span style={{ fontSize: "0.75rem", fontWeight: 700, color: style.color, background: style.bg, padding: "0.25rem 0.75rem", borderRadius: "20px" }}>
                    {labelOf(LABEL_STATUS_LOWONGAN, item.status)}
                  </span>
                  <span style={{ display: "flex", gap: "0.6rem", alignItems: "center", fontSize: "0.75rem", color: "#64748b", fontWeight: 600 }}>
                    <span>{item.kode}</span>
                    <span style={{ color: "#94a3b8" }}>{LABEL_JENIS_LOWONGAN[item.jenis] ?? item.jenis}</span>
                  </span>
                </div>

                <h3 style={{ fontSize: "1.05rem", color: "#0f172a", marginBottom: "0.35rem" }}>{item.judul}</h3>

                <div style={{ display: "flex", flexWrap: "wrap", gap: "1rem", fontSize: "0.78rem", color: "#94a3b8", marginBottom: "0.75rem" }}>
                  {item.posisi && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem", color: "#475569", fontWeight: 600 }}>
                      Posisi: {item.posisi}
                    </span>
                  )}
                  {item.perusahaan && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <Building2 size={13} /> {item.perusahaan}
                    </span>
                  )}
                  {item.kategori && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <Tag size={13} /> {item.kategori.nama}
                    </span>
                  )}
                  {item.kecamatan && (
                    <span style={{ display: "inline-flex", alignItems: "center", gap: "0.3rem" }}>
                      <MapPin size={13} /> {item.kecamatan.nama}
                    </span>
                  )}
                  <span>Sumber: {item.sumber ?? "-"}</span>
                  <span>{item.tayang_at ? formatTanggal(item.tayang_at) : ""}</span>
                </div>

                {item.deskripsi && (
                  <p style={{ fontSize: "0.875rem", color: "#64748b", lineHeight: 1.55, marginBottom: "0.75rem" }}>
                    {item.deskripsi}
                  </p>
                )}

                {item.catatan_admin && (
                  <p style={{ fontSize: "0.8rem", color: "#b45309", background: "#fffbeb", padding: "0.5rem 0.75rem", borderRadius: "8px", marginBottom: "0.75rem" }}>
                    Catatan: {item.catatan_admin}
                  </p>
                )}

                {sedangEdit && form && (
                  <div style={{ border: "1px solid #e2e8f0", borderRadius: "10px", padding: "1rem", marginBottom: "0.85rem", background: "#f8fafc" }}>
                    <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(220px, 1fr))", gap: "0.75rem" }}>
                      <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600 }}>
                        Posisi
                        <input
                          value={form.posisi}
                          onChange={(e) => setForm({ ...form, posisi: e.target.value })}
                          style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem" }}
                        />
                      </label>
                      <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600 }}>
                        Perusahaan
                        <input
                          value={form.perusahaan}
                          onChange={(e) => setForm({ ...form, perusahaan: e.target.value })}
                          style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem" }}
                        />
                      </label>
                      <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600 }}>
                        Jenis
                        <select
                          value={form.jenis}
                          onChange={(e) => setForm({ ...form, jenis: e.target.value as Lowongan["jenis"] })}
                          style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem" }}
                        >
                          <option value="karir">Karier</option>
                          <option value="magang">Magang</option>
                          <option value="part-time">Part Time</option>
                        </select>
                      </label>
                      <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600 }}>
                        Bidang Pekerjaan
                        <select
                          value={form.kategori_id}
                          onChange={(e) => setForm({ ...form, kategori_id: e.target.value })}
                          style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem" }}
                        >
                          <option value="">Tanpa kategori</option>
                          {meta.kategori.map((kategori) => (
                            <option key={kategori.id} value={String(kategori.id)}>
                              {kategori.nama}
                            </option>
                          ))}
                        </select>
                      </label>
                      <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600 }}>
                        Kecamatan
                        <select
                          value={form.kecamatan_id}
                          onChange={(e) => setForm({ ...form, kecamatan_id: e.target.value })}
                          style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem" }}
                        >
                          <option value="">Tanpa kecamatan</option>
                          {meta.kecamatan.map((kecamatan) => (
                            <option key={kecamatan.id} value={String(kecamatan.id)}>
                              {kecamatan.nama}
                            </option>
                          ))}
                        </select>
                      </label>
                    </div>

                    <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600, marginTop: "0.75rem" }}>
                      Deskripsi
                      <textarea
                        rows={3}
                        value={form.deskripsi}
                        onChange={(e) => setForm({ ...form, deskripsi: e.target.value })}
                        style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem", resize: "vertical" }}
                      />
                    </label>

                    <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600, marginTop: "0.75rem" }}>
                      Kualifikasi
                      <textarea
                        rows={2}
                        value={form.kualifikasi}
                        onChange={(e) => setForm({ ...form, kualifikasi: e.target.value })}
                        style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem", resize: "vertical" }}
                      />
                    </label>

                    <label style={{ display: "flex", flexDirection: "column", gap: "0.3rem", fontSize: "0.78rem", color: "#475569", fontWeight: 600, marginTop: "0.75rem" }}>
                      Cara Melamar
                      <textarea
                        rows={2}
                        value={form.cara_apply}
                        onChange={(e) => setForm({ ...form, cara_apply: e.target.value })}
                        style={{ padding: "0.45rem 0.6rem", border: "1px solid #cbd5e1", borderRadius: "8px", fontSize: "0.85rem", resize: "vertical" }}
                      />
                    </label>

                    <div style={{ display: "flex", gap: "0.5rem", marginTop: "0.85rem", flexWrap: "wrap" }}>
                      <button
                        type="button"
                        disabled={sedang}
                        onClick={() =>
                          kirim(
                            item,
                            {
                              posisi: form.posisi || null,
                              perusahaan: form.perusahaan || null,
                              jenis: form.jenis,
                              deskripsi: form.deskripsi || null,
                              kualifikasi: form.kualifikasi || null,
                              cara_apply: form.cara_apply || null,
                              kategori_id: form.kategori_id || null,
                              kecamatan_id: form.kecamatan_id || null,
                            },
                            `Lowongan ${item.kode} diperbarui.`,
                          )
                        }
                        style={{
                          display: "inline-flex",
                          alignItems: "center",
                          gap: "0.4rem",
                          padding: "0.45rem 1rem",
                          borderRadius: "8px",
                          fontSize: "0.82rem",
                          fontWeight: 600,
                          cursor: sedang ? "default" : "pointer",
                          border: "1px solid var(--primary)",
                          background: "var(--primary)",
                          color: "white",
                        }}
                      >
                        <Save size={14} /> {sedang ? "Menyimpan..." : "Simpan"}
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setEditId(null);
                          setForm(null);
                        }}
                        style={{
                          padding: "0.45rem 1rem",
                          borderRadius: "8px",
                          fontSize: "0.82rem",
                          fontWeight: 600,
                          cursor: "pointer",
                          border: "1px solid #e2e8f0",
                          background: "white",
                          color: "#64748b",
                        }}
                      >
                        Batal
                      </button>
                    </div>
                  </div>
                )}

                <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap" }}>
                  {item.status === "baru" && (
                    <>
                      <button
                        type="button"
                        disabled={sedang}
                        onClick={() => kirim(item, { status: "tayang" }, `Lowongan ${item.kode} ditayangkan.`)}
                        style={{
                          padding: "0.45rem 1rem",
                          borderRadius: "8px",
                          fontSize: "0.82rem",
                          fontWeight: 600,
                          cursor: sedang ? "default" : "pointer",
                          border: "1px solid var(--primary)",
                          background: "var(--primary)",
                          color: "white",
                          opacity: sedang ? 0.6 : 1,
                        }}
                      >
                        Tayangkan
                      </button>
                      <button
                        type="button"
                        disabled={sedang}
                        onClick={() =>
                          kirim(
                            item,
                            { status: "ditolak", catatan_admin: "Tidak sesuai dengan kurasi lowongan Tangsel." },
                            `Lowongan ${item.kode} ditolak.`,
                          )
                        }
                        style={{
                          padding: "0.45rem 1rem",
                          borderRadius: "8px",
                          fontSize: "0.82rem",
                          fontWeight: 600,
                          cursor: sedang ? "default" : "pointer",
                          border: "1px solid #fecaca",
                          background: "white",
                          color: "#dc2626",
                          opacity: sedang ? 0.6 : 1,
                        }}
                      >
                        Tolak
                      </button>
                    </>
                  )}

                  {(item.status === "tayang" || item.status === "ditolak" || item.status === "kedaluwarsa") && (
                    <button
                      type="button"
                      disabled={sedang}
                      onClick={() => kirim(item, { status: "baru" }, `Lowongan ${item.kode} dikembalikan ke kurasi.`)}
                      style={{
                        padding: "0.45rem 1rem",
                        borderRadius: "8px",
                        fontSize: "0.82rem",
                        fontWeight: 600,
                        cursor: sedang ? "default" : "pointer",
                        border: "1px solid #e2e8f0",
                        background: "white",
                        color: "#475569",
                        opacity: sedang ? 0.6 : 1,
                      }}
                    >
                      Kembalikan ke Kurasi
                    </button>
                  )}

                  <button
                    type="button"
                    disabled={sedang}
                    onClick={() => (sedangEdit ? setEditId(null) : bukaEdit(item))}
                    style={{
                      display: "inline-flex",
                      alignItems: "center",
                      gap: "0.35rem",
                      padding: "0.45rem 1rem",
                      borderRadius: "8px",
                      fontSize: "0.82rem",
                      fontWeight: 600,
                      cursor: sedang ? "default" : "pointer",
                      border: "1px solid #e2e8f0",
                      background: "white",
                      color: "#475569",
                      opacity: sedang ? 0.6 : 1,
                    }}
                  >
                    <Pencil size={14} /> {sedangEdit ? "Tutup" : "Koreksi"}
                  </button>

                  {item.url_asli && (
                    <a
                      href={item.url_asli}
                      target="_blank"
                      rel="noopener noreferrer"
                      style={{
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.35rem",
                        padding: "0.45rem 1rem",
                        borderRadius: "8px",
                        fontSize: "0.82rem",
                        fontWeight: 600,
                        border: "1px solid #e2e8f0",
                        color: "#475569",
                        textDecoration: "none",
                      }}
                    >
                      <ExternalLink size={14} /> Cek Sumber
                    </a>
                  )}

                  <Link
                    href={`/lowongan/${item.slug}`}
                    style={{
                      display: "inline-flex",
                      alignItems: "center",
                      gap: "0.35rem",
                      padding: "0.45rem 1rem",
                      borderRadius: "8px",
                      fontSize: "0.82rem",
                      fontWeight: 600,
                      border: "1px solid #e2e8f0",
                      color: "#475569",
                      textDecoration: "none",
                    }}
                  >
                    Lihat Halaman
                  </Link>

                  <button
                    type="button"
                    disabled={sedang}
                    onClick={() => hapus(item)}
                    style={{
                      display: "inline-flex",
                      alignItems: "center",
                      gap: "0.35rem",
                      marginLeft: "auto",
                      padding: "0.45rem 1rem",
                      borderRadius: "8px",
                      fontSize: "0.82rem",
                      fontWeight: 600,
                      cursor: sedang ? "default" : "pointer",
                      border: "1px solid #fecaca",
                      background: "white",
                      color: "#dc2626",
                      opacity: sedang ? 0.6 : 1,
                    }}
                  >
                    <Trash2 size={14} /> Hapus
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
