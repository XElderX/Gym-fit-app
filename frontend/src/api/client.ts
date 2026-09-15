const API = import.meta.env.VITE_API_URL || 'http://localhost:8000';

export class ApiError extends Error {
  constructor(
    public status: number,
    public body: any,
  ) {
    super(body?.message || 'Request failed');
  }
}

async function csrf() {
  await fetch(`${API}/sanctum/csrf-cookie`, { credentials: 'include' });
}

function xsrf() {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : '';
}

export async function request<T>(path: string, init: RequestInit = {}) {
  const method = (init.method || 'GET').toUpperCase();

  if (!['GET', 'HEAD'].includes(method)) {
    await csrf();
  }

  const res = await fetch(`${API}/api/v1${path}`, {
    ...init,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(method !== 'GET' ? { 'X-XSRF-TOKEN': xsrf() } : {}),
      ...(init.headers || {}),
    },
  });

  if (!res.ok) {
    let body: any = {};
    try {
      body = await res.json();
    } catch {}
    throw new ApiError(res.status, body);
  }

  if (res.status === 204) {
    return undefined as T;
  }

  return res.json() as Promise<T>;
}

export const api = {
  get: <T>(path: string) => request<T>(path),
  post: <T>(path: string, body?: any) =>
    request<T>(path, {
      method: 'POST',
      body: body === undefined ? undefined : JSON.stringify(body),
    }),
  put: <T>(path: string, body: any) =>
    request<T>(path, {
      method: 'PUT',
      body: JSON.stringify(body),
    }),
  patch: <T>(path: string, body: any) =>
    request<T>(path, {
      method: 'PATCH',
      body: JSON.stringify(body),
    }),
  delete: <T>(path: string) => request<T>(path, { method: 'DELETE' }),
};
