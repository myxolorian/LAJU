import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { errorMessages } from '../api/client'
import { useAuth } from '../auth/AuthContext'

const FIELDS = [
  { key: 'club_name', label: 'Nama klub', type: 'text', autoComplete: 'organization' },
  { key: 'sport_type', label: 'Cabang olahraga', type: 'text', autoComplete: 'off' },
  { key: 'name', label: 'Nama admin', type: 'text', autoComplete: 'name' },
  { key: 'email', label: 'Email', type: 'email', autoComplete: 'email' },
  { key: 'phone', label: 'No. HP (opsional)', type: 'tel', autoComplete: 'tel', optional: true },
  { key: 'password', label: 'Password', type: 'password', autoComplete: 'new-password' },
  {
    key: 'password_confirmation',
    label: 'Ulangi password',
    type: 'password',
    autoComplete: 'new-password',
  },
]

const EMPTY = Object.fromEntries(FIELDS.map((f) => [f.key, '']))

export default function RegisterClubPage() {
  const { registerClub } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState(EMPTY)
  const [errors, setErrors] = useState([])
  const [submitting, setSubmitting] = useState(false)

  const set = (key) => (event) => setForm((prev) => ({ ...prev, [key]: event.target.value }))

  async function onSubmit(event) {
    event.preventDefault()
    setSubmitting(true)
    setErrors([])
    try {
      // phone kosong tidak dikirim supaya lolos validasi `nullable`
      const payload = Object.fromEntries(Object.entries(form).filter(([, v]) => v !== ''))
      await registerClub(payload)
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
        <h1>Daftar klub baru</h1>
        <p className="muted">Akun ini otomatis menjadi admin klub.</p>

        {errors.length > 0 && (
          <div className="error" role="alert">
            <ul>
              {errors.map((message) => (
                <li key={message}>{message}</li>
              ))}
            </ul>
          </div>
        )}

        {FIELDS.map(({ key, label, type, autoComplete, optional }) => (
          <div className="field" key={key}>
            <label htmlFor={key}>{label}</label>
            <input
              id={key}
              type={type}
              autoComplete={autoComplete}
              required={!optional}
              value={form[key]}
              onChange={set(key)}
            />
          </div>
        ))}

        <button type="submit" disabled={submitting}>
          {submitting ? 'Memproses…' : 'Daftarkan klub'}
        </button>

        <p className="form-footer muted">
          Sudah punya akun? <Link to="/login">Masuk</Link>
        </p>
      </form>
    </main>
  )
}
