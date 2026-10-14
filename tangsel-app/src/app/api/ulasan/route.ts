import { NextResponse } from "next/server";
import { API_URL } from "@/lib/api";
import { authHeaders, getToken } from "@/lib/auth";

/**
 * Proxy kirim ulasan warga (multipart: rating, komentar, maks. 3 foto) ke Laravel.
 * Token diambil dari cookie httpOnly — klien tidak pernah melihat token.
 */
export async function POST(request: Request) {
  const token = await getToken();

  if (!token) {
    return NextResponse.json({ message: "Kamu perlu masuk untuk menulis ulasan." }, { status: 401 });
  }

  const formData = await request.formData();

  const response = await fetch(`${API_URL}/ulasan`, {
    method: "POST",
    headers: { Accept: "application/json", ...authHeaders(token) },
    body: formData,
    cache: "no-store",
  });

  const body = await response.json().catch(() => ({}));

  return NextResponse.json(body, { status: response.status });
}
