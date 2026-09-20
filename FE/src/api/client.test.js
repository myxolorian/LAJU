import { beforeEach, describe, expect, it } from 'vitest'
import { mockApi } from '../test/mockApi'
import { api, errorMessages, tokenStore } from './client'

describe('api client', () => {
  beforeEach(() => localStorage.clear())

  it('attaches the Sanctum bearer token when one is stored', async () => {
    tokenStore.set('1|abc')
    const calls = mockApi({ 'GET /ping': () => ({ status: 200, data: {} }) })

    await api.get('/ping')

    expect(calls[0].headers.Authorization).toBe('Bearer 1|abc')
    expect(calls[0].headers.Accept).toBe('application/json')
  })

  it('sends no Authorization header without a token', async () => {
    const calls = mockApi({ 'GET /ping': () => ({ status: 200, data: {} }) })

    await api.get('/ping')

    expect(calls[0].headers.Authorization).toBeUndefined()
  })

  it('clears the token and emits an event on 401', async () => {
    tokenStore.set('1|expired')
    mockApi({ 'GET /auth/me': () => ({ status: 401, data: { message: 'Unauthenticated.' } }) })
    let fired = false
    window.addEventListener('laju:unauthorized', () => (fired = true), { once: true })

    await expect(api.get('/auth/me')).rejects.toBeTruthy()

    expect(tokenStore.get()).toBeNull()
    expect(fired).toBe(true)
  })

  it('flattens Laravel validation errors', () => {
    const error = { response: { data: { errors: { email: ['a', 'b'], password: ['c'] } } } }
    expect(errorMessages(error)).toEqual(['a', 'b', 'c'])
    expect(errorMessages({})[0]).toMatch(/server/i)
  })
})
