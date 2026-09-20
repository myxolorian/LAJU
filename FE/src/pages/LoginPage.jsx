import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { errorMessages } from '../api/client'
import { useAuth } from '../auth/AuthContext'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [errors, setErrors] = useState([])
  const [submitting, setSubmitting] = useState(false)

  const set = (key) => (event) => setForm((prev) => ({ ...prev, [key]: event.target.value }))

  async function onSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setErrors([])
    try {
      await login(form)
      navigate('/dashboard', { replace: true })
    } catch (error) {
      setErrors(errorMessages(error))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="page">
      <form className="card" onSubmit={onSubmit} noValidate>
        <div className="brand">Laju</div>
        <h1>Masuk</h1>

        {errors.length > 0 && (
          <div className="error" role="alert">
            <ul>
              {errors.map((message) => (
                <li key={message}>{message}</li>
              ))}
            </ul>
          </div>
        )}

        <div className="field">
          <label htmlFor="email">Email</label>
          <input
            id="email"
            type="email"
            autoComplete="email"
            required
            value={form.email}
            onChange={set('email')}
          />
        </div>
        <div className="field">
          <label htmlFor="password">Password</label>
          <input
            id="password"
            type="password"
            autoComplete="current-password"
            required
            value={form.password}
            onChange={set('password')}
          />
        </div>

        <button type="submit" disabled={submitting}>
          {submitting ? 'Memproses…' : 'Masuk'}
        </button>

        <p className="form-footer muted">
          Belum punya klub? <Link to="/daftar-klub">Daftar klub baru</Link>
        </p>
      </form>
    </main>
  )
}
