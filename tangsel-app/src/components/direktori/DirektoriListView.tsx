import type { ReactNode } from "react";
import Link from "next/link";
import { MapPin, Search, Star } from "lucide-react";

export type DirektoriCardItem = {
  id: number;
  nama: string;
  href: string;
  jenisLabel: string;
  lokasi: string;
  rating: string;
  gambar: string | null;
  ikon?: ReactNode;
  accent?: string;
};

export type DirektoriPill = {
  label: string;
  jumlah: number;
  href: string;
  active: boolean;
};

type DirektoriListViewProps = {
  eyebrow: string;
  title: string;
  subtitle: string;
  accent: string;
  icon: ReactNode;
  searchAction: string;
  searchPlaceholder: string;
  searchValue: string;
  hiddenParams?: Record<string, string | undefined>;
  pills: DirektoriPill[];
  items: DirektoriCardItem[];
  total: number;
  emptyText: string;
};

export default function DirektoriListView({
  eyebrow,
  title,
  subtitle,
  accent,
  icon,
  searchAction,
  searchPlaceholder,
  searchValue,
  hiddenParams,
  pills,
  items,
  total,
  emptyText,
}: DirektoriListViewProps) {
  return (
    <div style={{ background: "var(--surface-hover)", minHeight: "100vh", paddingBottom: "var(--spacing-xxl)" }}>
      <section className="container" style={{ paddingTop: "var(--spacing-xl)", paddingBottom: "var(--spacing-lg)" }}>
        <span className="tag" style={{ background: accent }}>
          {eyebrow}
        </span>
        <h1 className="hero-title" style={{ color: "var(--text-main)", fontSize: "clamp(2rem, 4vw, 3rem)" }}>
          {title}
        </h1>
        <p style={{ fontSize: "1.1rem", color: "var(--text-muted)", maxWidth: "620px", marginBottom: "2rem" }}>
          {subtitle}
        </p>

        <form
          action={searchAction}
          method="get"
          className="search-bar"
          style={{ maxWidth: "620px", boxShadow: "0 10px 30px rgba(0,0,0,0.05)", border: "1px solid var(--border)" }}
        >
          {hiddenParams &&
            Object.entries(hiddenParams).map(([key, value]) =>
              value ? <input key={key} type="hidden" name={key} value={value} /> : null,
            )}
          <input
            type="text"
            name="q"
            defaultValue={searchValue}
            className="search-input"
            placeholder={searchPlaceholder}
          />
          <button type="submit" className="search-btn" aria-label="Cari">
            <Search size={18} />
          </button>
        </form>

        <div style={{ display: "flex", gap: "0.5rem", flexWrap: "wrap", marginTop: "1.25rem" }}>
          {pills.map((pill) => (
            <Link
              key={pill.label}
              href={pill.href}
              style={{
                display: "inline-flex",
                alignItems: "center",
                gap: "0.4rem",
                padding: "0.5rem 0.9rem",
                borderRadius: "999px",
                fontSize: "0.85rem",
                fontWeight: 600,
                border: "1px solid var(--border)",
                background: pill.active ? "var(--primary)" : "#fff",
                color: pill.active ? "#fff" : "var(--text-muted)",
              }}
            >
              {pill.label}
              <span style={{ opacity: 0.75, fontWeight: 500 }}>{pill.jumlah}</span>
            </Link>
          ))}
        </div>
      </section>

      <section className="container">
        <p style={{ color: "var(--text-muted)", marginBottom: "1rem", fontSize: "0.9rem" }}>
          {total} lokasi ditemukan
        </p>

        {items.length === 0 ? (
          <div
            style={{
              background: "#fff",
              border: "1px dashed var(--border)",
              borderRadius: "16px",
              padding: "3rem 1.5rem",
              textAlign: "center",
              color: "var(--text-muted)",
            }}
          >
            {emptyText}
          </div>
        ) : (
          <div
            style={{
              display: "grid",
              gridTemplateColumns: "repeat(auto-fill, minmax(260px, 1fr))",
              gap: "1rem",
            }}
          >
            {items.map((item) => (
              <Link
                key={item.id}
                href={item.href}
                style={{
                  display: "block",
                  background: "#fff",
                  borderRadius: "16px",
                  border: "1px solid var(--border)",
                  overflow: "hidden",
                  transition: "transform 0.2s, box-shadow 0.2s",
                }}
              >
                <div
                  style={{
                    height: "170px",
                    position: "relative",
                    background: `linear-gradient(135deg, ${item.accent ?? accent}, var(--primary-dark))`,
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                >
                  {item.gambar ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={item.gambar}
                      alt={item.nama}
                      style={{ width: "100%", height: "100%", objectFit: "cover" }}
                    />
                  ) : (
                    <span style={{ color: "rgba(255,255,255,0.75)" }}>{item.ikon ?? icon}</span>
                  )}
                  {item.rating !== "-" && (
                    <span
                      style={{
                        position: "absolute",
                        top: "0.75rem",
                        right: "0.75rem",
                        display: "inline-flex",
                        alignItems: "center",
                        gap: "0.25rem",
                        background: "rgba(255,255,255,0.92)",
                        backdropFilter: "blur(6px)",
                        padding: "0.3rem 0.6rem",
                        borderRadius: "8px",
                        fontSize: "0.75rem",
                        fontWeight: 700,
                        color: "var(--text-main)",
                      }}
                    >
                      <Star size={13} fill="currentColor" color="#FBBF24" /> {item.rating}
                    </span>
                  )}
                </div>
                <div style={{ padding: "1rem" }}>
                  <h3 style={{ fontSize: "1.05rem", marginBottom: "0.4rem", lineHeight: 1.35 }}>{item.nama}</h3>
                  <div
                    style={{
                      display: "flex",
                      alignItems: "center",
                      gap: "0.4rem",
                      color: "var(--text-muted)",
                      fontSize: "0.8rem",
                      marginBottom: "0.6rem",
                    }}
                  >
                    <MapPin size={13} /> {item.lokasi}
                  </div>
                  <span style={{ fontSize: "0.75rem", fontWeight: 600, color: "var(--primary)" }}>
                    {item.jenisLabel}
                  </span>
                </div>
              </Link>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
