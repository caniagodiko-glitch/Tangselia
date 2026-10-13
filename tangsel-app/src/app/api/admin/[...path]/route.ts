import { NextResponse } from "next/server";
import { API_URL } from "@/lib/api";
import { authHeaders, getToken } from "@/lib/auth";

/**
 * Proxy generik ke endpoint admin Laravel (`/api/admin/*` → `/admin/*`).
 * Wajib punya cookie token; otorisasi sebenarnya tetap ditegakkan backend
 * lewat middleware `auth:sanctum` + `role:admin`.
 */
async function proxy(request: Request, method: "GET" | "POST" | "PUT" | "DELETE") {
  const token = await getToken();

  if (!token) {
    return NextResponse.json({ message: "Belum masuk." }, { status: 401 });
  }

  const { pathname, search } = new URL(request.url);
  const target = pathname.replace(/^\/api\/admin/, "/admin");
  const headers: Record<string, string> = { Accept: "application/json", ...authHeaders(token) };

  const init: RequestInit = { method, headers, cache: "no-store" };

  if (method === "POST" || method === "PUT") {
    const body = await request.text();
    if (body) {
      init.body = body;
      headers["Content-Type"] = request.headers.get("content-type") ?? "application/json";
    }
  }

  const response = await fetch(`${API_URL}${target}${search}`, init);
  const text = await response.text();

  return new NextResponse(text, {
    status: response.status,
    headers: {
      "Content-Type": response.headers.get("content-type") ?? "application/json",
    },
  });
}

export async function GET(request: Request) {
  return proxy(request, "GET");
}

export async function POST(request: Request) {
  return proxy(request, "POST");
}

export async function PUT(request: Request) {
  return proxy(request, "PUT");
}

export async function DELETE(request: Request) {
  return proxy(request, "DELETE");
}
