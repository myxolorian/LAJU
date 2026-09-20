import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import * as authApi from '../api/auth'
import { tokenStore, UNAUTHORIZED_EVENT } from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  // 'loading' selama token yang tersimpan sedang diverifikasi lewat /auth/me
  const [status, setStatus] = useState(() => (tokenStore.get() ? 'loading' : 'guest'))

  useEffect(() => {
    if (status !== 'loading') return undefined
    let active = true
    authApi
      .fetchMe()
      .then((me) => {
        if (!active) return
        setUser(me)
        setStatus('authed')
      })
      .catch(() => {
        if (!active) return
        tokenStore.clear()
        setStatus('guest')
      })
    return () => {
      active = false
    }
  }, [status])

  useEffect(() => {
    const onUnauthorized = () => {
      setUser(null)
      setStatus('guest')
    }
    window.addEventListener(UNAUTHORIZED_EVENT, onUnauthorized)
    return () => window.removeEventListener(UNAUTHORIZED_EVENT, onUnauthorized)
  }, [])

  const accept = useCallback(({ token, user: nextUser }) => {
    tokenStore.set(token)
    setUser(nextUser)
    setStatus('authed')
  }, [])

  const login = useCallback(async (credentials) => accept(await authApi.login(credentials)), [accept])

  const registerClub = useCallback(
    async (payload) => accept(await authApi.registerClub(payload)),
    [accept],
  )

  const logout = useCallback(async () => {
    try {
      await authApi.logout()
    } catch {
      /* token mungkin sudah tidak valid — tetap keluar di sisi klien */
    }
    tokenStore.clear()
    setUser(null)
    setStatus('guest')
  }, [])

  const value = useMemo(
    () => ({ user, status, login, registerClub, logout }),
    [user, status, login, registerClub, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth harus dipakai di dalam <AuthProvider>')
  return ctx
}
