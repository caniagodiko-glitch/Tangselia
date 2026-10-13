import { NextResponse } from "next/server";
import { API_URL } from "@/lib/api";
import { TOKEN_COOKIE, authHeaders, getToken } from "@/lib/auth";

/**
 * Cabut token di Laravel (best effort), lalu hapus cookie httpOnly.
 */
export async function POST() {
  const token = await getToken();

  if (token) {
    await fetch(`${API_URL}/auth/logout`, {
      method: "POST",
      headers: { Accept: "application/json", ...authHeaders(token) },
      cache: "no-store",
    }).catch(() => undefined);
  }

  const response = NextResponse.json({ message: "Berhasil keluar." });
  response.cookies.set({
    name: TOKEN_COOKIE,
    value: "",
    httpOnly: true,
    sameSite: "lax",
    path: "/",
    maxAge: 0,
  });

  return response;
}
