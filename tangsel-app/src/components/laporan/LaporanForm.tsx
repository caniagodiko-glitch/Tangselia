"use client";

import { useRef, useState, type ChangeEvent, type FormEvent } from "react";
import Link from "next/link";
import { Camera, CheckCircle2, Crosshair, MapPin, X } from "lucide-react";
import type { MetaLaporan } from "@/lib/api";

type Props = {
  kategori: MetaLaporan["kategori"];
  kecamatan: MetaLaporan["kecamatan"];
};

type Koordinat = { lat: number; lng: number };

export default function LaporanForm({ kategori, kecamatan }: Props) {
  const [kategoriId, setKategoriId] = useState<number | null>(kategori[0]?.id ?? null);
  const [kecamatanId, setKecamatanId] = useState("");
  const [judul, setJudul] = useState("");
  const [deskripsi, setDeskripsi] = useState("");
  const [koordinat, setKoordinat] = useState<Koordinat | null>(null);
  const [foto, setFoto] = useState<File[]>([]);
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [sukses, setSukses] = useState<{ kode: string; status: string } | null>(null);
  const fileRef = useRef<HTMLInputElement>(null);

  function pilihFoto(event: ChangeEvent<HTMLInputElement>) {
    setFoto(Array.from(event.target.files ?? []).slice(0, 3));
  }

  function ambilLokasi() {
    if (!navigator.geolocation) {
      setError("Perangkat ini tidak mendukung layanan lokasi.");
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (posisi) => {
        setKoordinat({ lat: posisi.coords.latitude, lng: posisi.coords.longitude });
        setError(null);
      },
      () => setError("Gagal mengambil lokasi. Izinkan akses lokasi lalu coba lagi."),
      { enableHighAccuracy: true, timeout: 10000 },
    );
  }

  async function kirim(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (!kategoriId) {
      setError("Pilih kategori laporan terlebih dahulu.");
      return;
    }
    if (!koordinat) {
      setError("Tentukan titik lokasi laporan.");
      return;
    }

    setPending(true);

    const form = new FormData();
    form.set("kategori_id", String(kategoriId));
    if (kecamatanId) {
      form.set("kecamatan_id", kecamatanId);
    }
    form.set("judul", judul);
    form.set("deskripsi", deskripsi);
    form.set("latitude", String(koordinat.lat));
    form.set("longitude", String(koordinat.lng));
    foto.forEach((file) => form.append("foto[]", file));

    try {
      const response = await fetch("/api/laporan", { method: "POST", body: form });
      const data = (await response.json().catch(() => ({}))) as {
        message?: string;
        data?: { kode?: string; status?: string };
      };

      if (!response.ok) {
        setError(data.message ?? "Gagal mengirim laporan. Periksa kembali isian kamu.");
        setPending(false);
        return;
      }

      setSukses({ kode: data.data?.kode ?? "-", status: data.data?.status ?? "baru" });
    } catch {
      setError("Tidak bisa menghubungi server. Pastikan koneksi aktif.");
      setPending(false);
    }
  }

  if (sukses) {
    return (
      <div style={{ background: "#fff", border: "1px solid var(--border)", borderRadius: "24px", padding: "var(--spacing-xl)", textAlign: "center" }}>
        <div style={{ width: "72px", height: "72px", borderRadius: "50%", background: "#dcfce7", color: "#16a34a", display: "flex", alignItems: "center", justifyContent: "center", margin: "0 auto 1.5rem" }}>
          <CheckCircle2 size={38} />
        </div>
        <h2 style={{ fontSize: "1.5rem", marginBottom: "0.5rem" }}>Laporan terkirim</h2>
        <p style={{ color: "var(--text-muted)", marginBottom: "1.5rem" }}>
          Simpan kode pelacakan berikut untuk memantau status laporan.
        </p>
        <div style={{ display: "inline-block", padding: "0.9rem 1.5rem", background: "var(--surface-hover)", borderRadius: "12px", fontFamily: "var(--font-display)", fontSize: "1.5rem", letterSpacing: "0.05em", marginBottom: "1.5rem" }}>
          {sukses.kode}
        </div>
        <div style={{ display: "flex", gap: "0.75rem", justifyContent: "center", flexWrap: "wrap" }}>
          <Link href="/laporan?tab=mine" className="btn btn-primary">
            Lihat Laporan Saya
          </Link>
          <Link href="/laporan" className="btn btn-outline">
            Kembali ke Feed
          </Link>
        </div>
      </div>
    );
  }

  return (
    <form onSubmit={kirim} style={{ display: "flex", flexDirection: "column", gap: "var(--spacing-lg)" }}>
      <div>
        <label className="form-label" style={{ fontSize: "1.1rem", marginBottom: "1rem" }}>
          Pilih Kategori Laporan
        </label>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(160px, 1fr))", gap: "1rem" }}>
          {kategori.map((item) => {
            const aktif = kategoriId === item.id;
            return (
              <button
                type="button"
                key={item.id}
                onClick={() => setKategoriId(item.id)}
                style={{
                  border: aktif ? "2px solid var(--primary)" : "1px solid var(--border)",
                  background: aktif ? "#e6f4f1" : "white",
                  color: aktif ? "var(--primary)" : "var(--text-main)",
                  padding: "1rem",
                  borderRadius: "12px",
                  textAlign: "center",
                  fontWeight: 600,
                  cursor: "pointer",
                  transition: "all 0.2s",
                }}
              >
                {item.ikon ? `${item.ikon} ` : ""}
                {item.nama}
              </button>
            );
          })}
        </div>
      </div>

      <div className="form-group">
        <label className="form-label" style={{ fontSize: "1.1rem" }}>
          Judul Laporan
        </label>
        <input
          type="text"
          required
          maxLength={255}
          className="form-control"
          value={judul}
          onChange={(event) => setJudul(event.target.value)}
          placeholder="Contoh: Lampu PJU Padam di Jl. Ciater"
          style={{ fontSize: "1.1rem", padding: "1rem" }}
        />
      </div>

      <div className="form-group">
        <label className="form-label" style={{ fontSize: "1.1rem" }}>
          Deskripsi Detail
        </label>
        <textarea
          required
          minLength={20}
          maxLength={5000}
          className="form-control"
          value={deskripsi}
          onChange={(event) => setDeskripsi(event.target.value)}
          placeholder="Jelaskan kondisi secara spesifik agar petugas mudah memahami (minimal 20 karakter)..."
          style={{ minHeight: "150px", fontSize: "1.1rem", padding: "1rem" }}
        />
      </div>

      <div className="form-group">
        <label className="form-label" style={{ fontSize: "1.1rem" }}>
          Kecamatan (opsional)
        </label>
        <select className="form-control" value={kecamatanId} onChange={(event) => setKecamatanId(event.target.value)} style={{ padding: "1rem" }}>
          <option value="">Pilih kecamatan…</option>
          {kecamatan.map((item) => (
            <option key={item.id} value={item.id}>
              {item.nama}
            </option>
          ))}
        </select>
      </div>

      <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fit, minmax(240px, 1fr))", gap: "1.5rem" }}>
        <div>
          <label className="form-label" style={{ fontSize: "1.1rem" }}>
            Foto Kondisi (maks. 3)
          </label>
          <input ref={fileRef} type="file" accept="image/*" multiple hidden onChange={pilihFoto} />
          <button
            type="button"
            onClick={() => fileRef.current?.click()}
            style={{ width: "100%", border: "2px dashed var(--border)", borderRadius: "16px", minHeight: "160px", display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: "0.5rem", color: "var(--text-muted)", cursor: "pointer", background: "var(--surface-hover)", padding: "1rem" }}
          >
            <Camera size={32} />
            <span style={{ fontWeight: 500 }}>{foto.length > 0 ? `${foto.length} foto dipilih` : "Ambil / pilih foto"}</span>
          </button>
          {foto.length > 0 && (
            <ul style={{ listStyle: "none", marginTop: "0.75rem", display: "flex", flexDirection: "column", gap: "0.35rem" }}>
              {foto.map((file, index) => (
                <li key={`${file.name}-${index}`} style={{ display: "flex", alignItems: "center", justifyContent: "space-between", gap: "0.5rem", fontSize: "0.85rem", color: "var(--text-muted)" }}>
                  <span style={{ overflow: "hidden", textOverflow: "ellipsis", whiteSpace: "nowrap" }}>{file.name}</span>
                  <button type="button" onClick={() => setFoto((current) => current.filter((_, i) => i !== index))} style={{ border: "none", background: "none", cursor: "pointer", color: "var(--error)" }} aria-label="Hapus foto">
                    <X size={14} />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div>
          <label className="form-label" style={{ fontSize: "1.1rem" }}>
            Titik Lokasi
          </label>
          <button
            type="button"
            onClick={ambilLokasi}
            style={{ width: "100%", border: "1px solid var(--border)", borderRadius: "16px", minHeight: "160px", display: "flex", flexDirection: "column", alignItems: "center", justifyContent: "center", gap: "0.5rem", color: "var(--primary)", cursor: "pointer", background: koordinat ? "#e6f4f1" : "var(--surface-hover)", padding: "1rem" }}
          >
            {koordinat ? <Crosshair size={32} /> : <MapPin size={32} />}
            <span style={{ fontWeight: 600 }}>{koordinat ? "Lokasi terdeteksi" : "Gunakan lokasi saya"}</span>
            {koordinat && (
              <span style={{ fontSize: "0.8rem", color: "var(--text-muted)" }}>
                {koordinat.lat.toFixed(5)}, {koordinat.lng.toFixed(5)}
              </span>
            )}
          </button>
        </div>
      </div>

      {error && (
        <p role="alert" style={{ color: "var(--error)", background: "#fef2f2", padding: "0.75rem 1rem", borderRadius: "8px", fontSize: "0.9rem" }}>
          {error}
        </p>
      )}

      <div style={{ marginTop: "1rem" }}>
        <button type="submit" className="btn btn-primary" disabled={pending} style={{ width: "100%", padding: "1.25rem", fontSize: "1.25rem", borderRadius: "16px", opacity: pending ? 0.7 : 1 }}>
          {pending ? "Mengirim..." : "Kirim Laporan"}
        </button>
      </div>
    </form>
  );
}
