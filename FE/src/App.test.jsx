import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { beforeEach, describe, expect, it } from 'vitest'
import { tokenStore } from './api/client'
import { AppRoutes } from './App'
import { AuthProvider } from './auth/AuthContext'
import { mockApi, sampleUser } from './test/mockApi'

function renderAt(path) {
  return render(
    <MemoryRouter initialEntries={[path]}>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </MemoryRouter>,
  )
}

describe('routing & auth', () => {
  beforeEach(() => localStorage.clear())

  it('redirects a guest from /dashboard to the login page', async () => {
    renderAt('/dashboard')
    expect(await screen.findByRole('heading', { name: /masuk/i })).toBeInTheDocument()
  })

  it('logs in and shows the logged-in user on the dashboard', async () => {
    const calls = mockApi({
      'POST /auth/login': () => ({ status: 200, data: { token: '1|tok', user: sampleUser } }),
    })
    const user = userEvent.setup()
    renderAt('/login')

    await user.type(screen.getByLabelText(/email/i), 'budi@example.com')
    await user.type(screen.getByLabelText(/password/i), 'rahasia-banget-123')
    await user.click(screen.getByRole('button', { name: /^masuk$/i }))

    expect(await screen.findByRole('heading', { name: /dashboard/i })).toBeInTheDocument()
    expect(screen.getByText('Budi Santoso')).toBeInTheDocument()
    expect(screen.getAllByText('Garuda Muda FC').length).toBeGreaterThan(0)
    expect(tokenStore.get()).toBe('1|tok')
    expect(JSON.parse(calls[0].data)).toEqual({
      email: 'budi@example.com',
      password: 'rahasia-banget-123',
    })
  })

  it('shows the server error when login fails', async () => {
    mockApi({
      'POST /auth/login': () => ({
        status: 422,
        data: { message: 'x', errors: { email: ['Email atau password salah.'] } },
      }),
    })
    const user = userEvent.setup()
    renderAt('/login')

    await user.type(screen.getByLabelText(/email/i), 'a@example.com')
    await user.type(screen.getByLabelText(/password/i), 'salah')
    await user.click(screen.getByRole('button', { name: /^masuk$/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Email atau password salah.')
    expect(tokenStore.get()).toBeNull()
  })

  it('restores the session from a stored token via /auth/me', async () => {
    tokenStore.set('1|stored')
    mockApi({ 'GET /auth/me': () => ({ status: 200, data: { user: sampleUser } }) })

    renderAt('/dashboard')

    expect(await screen.findByText('budi@example.com')).toBeInTheDocument()
  })

  it('registers a new club and lands on the dashboard', async () => {
    const calls = mockApi({
      'POST /auth/register-club': () => ({ status: 201, data: { token: '2|new', user: sampleUser } }),
    })
    const user = userEvent.setup()
    renderAt('/daftar-klub')

    await user.type(screen.getByLabelText(/nama klub/i), 'Garuda Muda FC')
    await user.type(screen.getByLabelText(/cabang olahraga/i), 'sepak bola')
    await user.type(screen.getByLabelText(/nama admin/i), 'Budi Santoso')
    await user.type(screen.getByLabelText(/^email/i), 'budi@example.com')
    await user.type(screen.getByLabelText(/^password/i), 'rahasia-banget-123')
    await user.type(screen.getByLabelText(/ulangi password/i), 'rahasia-banget-123')
    await user.click(screen.getByRole('button', { name: /daftarkan klub/i }))

    await waitFor(() => expect(screen.getByRole('heading', { name: /dashboard/i })).toBeInTheDocument())
    const body = JSON.parse(calls[0].data)
    expect(body.club_name).toBe('Garuda Muda FC')
    expect(body).not.toHaveProperty('phone')
  })
})
