import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from './AuthContext'

function Splash() {
  return (
    <div className="page" role="status">
      <p className="mono muted">Memuat…</p>
    </div>
  )
}

/** Hanya untuk user yang sudah login. */
export function RequireAuth() {
  const { status } = useAuth()
  const location = useLocation()

  if (status === 'loading') return <Splash />
  if (status !== 'authed') return <Navigate to="/login" replace state={{ from: location }} />
  return <Outlet />
}

/** Halaman login/daftar: kalau sudah login langsung ke dashboard. */
export function GuestOnly() {
  const { status } = useAuth()

  if (status === 'loading') return <Splash />
  if (status === 'authed') return <Navigate to="/dashboard" replace />
  return <Outlet />
}
