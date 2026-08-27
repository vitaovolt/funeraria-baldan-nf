import { createContext, useContext, useEffect, useState } from 'react'
import { fetchMe, login as apiLogin, logout as apiLogout } from '../api/auth'
import { getAuthToken, setAuthToken, USER_KEY } from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem(USER_KEY) || 'null')
    } catch {
      return null
    }
  })
  const [loading, setLoading] = useState(Boolean(getAuthToken()))

  useEffect(() => {
    let alive = true
    if (!getAuthToken()) {
      setLoading(false)
      return undefined
    }

    fetchMe()
      .then((payload) => {
        if (!alive) return
        setUser(payload.data)
        localStorage.setItem(USER_KEY, JSON.stringify(payload.data))
      })
      .catch(() => {
        if (!alive) return
        setAuthToken(null)
        setUser(null)
        localStorage.removeItem(USER_KEY)
      })
      .finally(() => {
        if (alive) setLoading(false)
      })

    return () => {
      alive = false
    }
  }, [])

  async function login(email, password) {
    const payload = await apiLogin(email, password)
    setAuthToken(payload.data.token)
    setUser(payload.data.user)
    localStorage.setItem(USER_KEY, JSON.stringify(payload.data.user))
    return payload.data.user
  }

  async function logout() {
    try {
      if (getAuthToken()) await apiLogout()
    } catch {
      // token já inválido
    } finally {
      setAuthToken(null)
      setUser(null)
      localStorage.removeItem(USER_KEY)
    }
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, isAuthenticated: Boolean(user) }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth deve ser usado dentro de AuthProvider')
  return ctx
}
