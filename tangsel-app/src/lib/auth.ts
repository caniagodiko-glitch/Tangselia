import { cookies } from "next/headers";
import { ApiError, apiFetch, type Query } from "./api";

/**
 * Auth frontend memakai pola BFF: token Sanctum dari Laravel disimpan di
 * cookie httpOnly (tidak pernah terekspos ke JavaScript). Semua panggilan
 * yang butuh login dilakukan dari sisi server dengan menambahkan header
 * `Authorization: Bearer <token>`.
 */
export const TOKEN_COOKIE = "tangsel_token";

/** Umur cookie token (7 hari), mengikuti masa berlaku token Sanctum default. */
export const TOKEN_MAX_AGE = 60 * 60 * 24 * 7;

export type AuthUser = {
  id: number;
  nama: string;
  email: string;
  role: string;
  telepon: string | null;
  aktif: boolean;
};

/** Baca token dari cookie (hanya bisa dipakai di server). */
export async function getToken(): Promise<string | null> {
  const store = await cookies();
  return store.get(TOKEN_COOKIE)?.value ?? null;
}

export function authHeaders(token: string | null): Record<string, string> {
  return token ? { Authorization: `Bearer ${token}` } : {};
}

/** Profil pengguna yang sedang masuk, atau `null` bila belum/tidak valid. */
export async function getCurrentUser(): Promise<AuthUser | null> {
  const token = await getToken();

  if (!token) {
    return null;
  }

  try {
    const response = await apiFetch<{ data: AuthUser }>("/auth/me", undefined, {
      headers: authHeaders(token),
    });
    return response.data;
  } catch {
    return null;
  }
}

/** Fetch terautentikasi; melempar 401 bila belum masuk. */
export async function authedFetch<T>(path: string, query?: Query): Promise<T> {
  const token = await getToken();

  if (!token) {
    throw new ApiError(401, "Kamu perlu masuk terlebih dahulu.");
  }

  return apiFetch<T>(path, query, { headers: authHeaders(token) });
}
