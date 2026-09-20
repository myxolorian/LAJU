import axios from 'axios'

const TOKEN_KEY = 'laju.token'

// localStorage bisa melempar (private mode / diblokir) → jangan sampai app crash.
export const tokenStore = {
  get() {
    try {
      return localStorage.getItem(TOKEN_KEY)
    } catch {
      return null
    }
  },
  set(token) {
    try {
      localStorage.setItem(TOKEN_KEY, token)
    } catch {
      /* abaikan */
    }
  },
  clear() {
    try {
      localStorage.removeItem(TOKEN_KEY)
    } catch {
      /* abaikan */
    }
  },
}

export const UNAUTHORIZED_EVENT = 'laju:unauthorized'

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
})

// Otomatis attach token Sanctum ke setiap request.
api.interceptors.request.use((config) => {
  const token = tokenStore.get()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Token ditolak server (kedaluwarsa/dicabut) → buang token & kabari AuthProvider.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && tokenStore.get()) {
      tokenStore.clear()
      window.dispatchEvent(new Event(UNAUTHORIZED_EVENT))
    }
    return Promise.reject(error)
  },
)

/** Ratakan pesan error Laravel (422 `errors` atau `message`) jadi array string. */
export function errorMessages(error) {
  const data = error?.response?.data
  if (data?.errors) {
    return Object.values(data.errors).flat()
  }
  if (data?.message) {
    return [data.message]
  }
  return ['Tidak dapat terhubung ke server. Coba lagi.']
}
