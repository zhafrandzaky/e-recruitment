import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../../src/stores/auth'

vi.mock('../../src/composables/useApi', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

import api from '../../src/composables/useApi'

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('initialises as unauthenticated when localStorage is empty', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
    expect(auth.user).toBeNull()
    expect(auth.token).toBeNull()
  })

  it('login stores token and user in localStorage', async () => {
    const mockUser = { id: 'uuid-1', name: 'Test', email: 'test@test.com', role: 'applicant' as const }
    ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'abc123', user: mockUser },
    })

    const auth = useAuthStore()
    await auth.login('test@test.com', 'password')

    expect(auth.isAuthenticated).toBe(true)
    expect(auth.user?.email).toBe('test@test.com')
    expect(localStorage.getItem('auth_token')).toBe('abc123')
  })

  it('logout clears auth state', async () => {
    const mockUser = { id: 'u', name: 'A', email: 'a@a.com', role: 'applicant' as const }
    ;(api.post as ReturnType<typeof vi.fn>)
      .mockResolvedValueOnce({ data: { token: 'tok', user: mockUser } }) // login
      .mockResolvedValueOnce({ data: {} }) // logout

    const auth = useAuthStore()
    await auth.login('a@a.com', 'password')
    expect(auth.isAuthenticated).toBe(true)

    await auth.logout()

    expect(auth.isAuthenticated).toBe(false)
    expect(localStorage.getItem('auth_token')).toBeNull()
  })

  it('isHrAdmin is true only for hr_admin role', async () => {
    const mockUser = { id: 'u', name: 'HR', email: 'hr@test.com', role: 'hr_admin' as const }
    ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { token: 'tok', user: mockUser },
    })

    const auth = useAuthStore()
    await auth.login('hr@test.com', 'password')

    expect(auth.isHrAdmin).toBe(true)
    expect(auth.isApplicant).toBe(false)
  })

  it('forgotPassword returns the message from the API', async () => {
    ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: { message: 'Jika email terdaftar, link reset password telah dikirimkan.' },
    })

    const auth = useAuthStore()
    const msg = await auth.forgotPassword('test@test.com')

    expect(msg).toContain('link reset password')
  })
})
