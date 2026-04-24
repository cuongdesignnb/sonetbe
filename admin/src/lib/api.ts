type ApiError = {
  message?: string;
  errors?: Record<string, string[]>;
};

export class HttpError extends Error {
  status: number;
  data: unknown;

  constructor(status: number, data: unknown) {
    super(`HTTP ${status}`);
    this.status = status;
    this.data = data;
  }
}

export function getToken(): string | null {
  return localStorage.getItem("admin_token");
}

export function setToken(token: string | null) {
  if (!token) localStorage.removeItem("admin_token");
  else localStorage.setItem("admin_token", token);
}

export async function apiFetch<T>(
  path: string,
  init: RequestInit = {},
): Promise<T> {
  const token = getToken();
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");

  if (token) headers.set("Authorization", `Bearer ${token}`);

  // Auto-set Content-Type for string bodies (JSON.stringify)
  if (typeof init.body === "string" && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const res = await fetch(path, {
    ...init,
    headers,
  });

  const data = await res.json().catch(() => null);
  if (!res.ok) throw new HttpError(res.status, data);
  return data as T;
}

export async function apiForm<T>(path: string, form: FormData): Promise<T> {
  const token = getToken();
  const headers = new Headers();
  headers.set("Accept", "application/json");
  if (token) headers.set("Authorization", `Bearer ${token}`);

  const res = await fetch(path, {
    method: "POST",
    headers,
    body: form,
  });

  const data = await res.json().catch(() => null);
  if (!res.ok) throw new HttpError(res.status, data);
  return data as T;
}

export function extractMessage(err: unknown): string {
  if (err instanceof HttpError) {
    const data = err.data as ApiError | null;
    if (data?.message) return data.message;
    return `HTTP ${err.status}`;
  }
  if (err instanceof Error) return err.message;
  return "Unknown error";
}
