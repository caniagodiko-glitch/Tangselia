"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { LogOut } from "lucide-react";

export default function LogoutButton({ className = "btn btn-outline" }: { className?: string }) {
  const router = useRouter();
  const [busy, setBusy] = useState(false);

  async function keluar() {
    setBusy(true);
    await fetch("/api/auth/logout", { method: "POST" }).catch(() => undefined);
    router.replace("/");
    router.refresh();
  }

  return (
    <button type="button" className={className} onClick={keluar} disabled={busy} style={{ display: "inline-flex", alignItems: "center", gap: "0.5rem", opacity: busy ? 0.7 : 1 }}>
      <LogOut size={16} /> {busy ? "Keluar..." : "Keluar"}
    </button>
  );
}
