import { NextResponse } from "next/server";
import { API_URL } from "@/lib/api";
import { TOKEN_COOKIE, TOKEN_MAX_AGE } from "@/lib/auth";

type AuthPayload = { data?: unknown; token?: string; message?: string; errors?: unknown };

/**
 * Proxy registrasi warga ke Laravel, lalu simpan token ke cookie httpOnly.
 */
export async function POST(request: Request) {
  const payload = await request.json().catch(() => null);

  if (!payload) {
    return NextResponse.json({ message: "Permintaan tidak valid." }, { status: 400 });
  }

  const response = await fetch(`${API_URL}/auth/register`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(payload),
    cache: "no-store",
  });

  const body = (await response.json().catch(() => ({}))) as AuthPayload;

  if (!response.ok || !body.token) {
    return NextResponse.json(
      { message: body.message ?? "Pendaftaran gagal.", errors: body.errors },
      { status: response.status === 200 || response.status === 201 ? 502 : response.status },
    );
  }

  const result = NextResponse.json({ user: body.data ?? null }, { status: 201 });
  result.cookies.set({
    name: TOKEN_COOKIE,
    value: body.token,
    httpOnly: true,
    sameSite: "lax",
    path: "/",
    secure: process.env.NODE_ENV === "production",
    maxAge: TOKEN_MAX_AGE,
  });

  return result;
}
