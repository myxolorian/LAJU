import { api } from './client'

export const registerClub = (payload) =>
  api.post('/auth/register-club', payload).then((r) => r.data)

export const login = (payload) => api.post('/auth/login', payload).then((r) => r.data)

export const fetchMe = () => api.get('/auth/me').then((r) => r.data.user)

export const logout = () => api.post('/auth/logout')
