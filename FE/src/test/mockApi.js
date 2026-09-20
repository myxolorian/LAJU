import { api } from '../api/client'

/**
 * Ganti adapter axios dengan handler in-memory. `routes` = { 'POST /auth/login': (config) => ({status, data}) }.
 * Mengembalikan daftar request yang masuk supaya test bisa memeriksa header/body.
 */
export function mockApi(routes) {
  const calls = []
  api.defaults.adapter = async (config) => {
    calls.push(config)
    const key = `${config.method.toUpperCase()} ${config.url}`
    const handler = routes[key]
    const { status, data } = handler ? handler(config) : { status: 404, data: { message: 'Not found' } }
    const response = { data, status, statusText: String(status), headers: {}, config }
    if (status >= 400) {
      const error = new Error(`Request failed with status code ${status}`)
      error.response = response
      error.config = config
      throw error
    }
    return response
  }
  return calls
}

export const sampleUser = {
  id: 1,
  name: 'Budi Santoso',
  email: 'budi@example.com',
  phone: null,
  club_members: [
    {
      id: 1,
      club_id: 1,
      role: 'admin',
      status: 'aktif',
      joined_at: '2026-09-20T09:38:35.000000Z',
      club: {
        id: 1,
        name: 'Garuda Muda FC',
        sport_type: 'sepak bola',
        logo_url: null,
        subscription_plan: 'free',
        subscription_status: 'active',
      },
    },
  ],
}
