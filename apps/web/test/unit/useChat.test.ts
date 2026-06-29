import { describe, it, expect, beforeEach, vi } from 'vitest'

vi.mock('../../src/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

import api from '../../src/composables/useApi'
import { useChat } from '../../src/composables/useChat'

describe('useChat', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetchMessages returns the unwrapped data array', async () => {
    const messages = [{ id: 'm1', content: 'hi', sender_id: 'u1', sent_at: '2026-06-30T00:00:00Z' }]
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: messages } })

    const { fetchMessages } = useChat()
    const result = await fetchMessages('app-1')

    expect(api.get).toHaveBeenCalledWith('/applications/app-1/messages')
    expect(result).toEqual(messages)
  })

  it('fetchMessages returns [] and sets error on failure', async () => {
    ;(api.get as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { data: { error: { message: 'Gagal memuat pesan.' } } },
    })

    const { fetchMessages, error } = useChat()
    const result = await fetchMessages('app-1')

    expect(result).toEqual([])
    expect(error.value).toBe('Gagal memuat pesan.')
  })

  it('sendMessage posts content and returns the created message', async () => {
    const created = { id: 'm2', content: 'halo', sender_id: 'u1', sent_at: '2026-06-30T00:00:00Z' }
    ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValue({ data: created })

    const { sendMessage } = useChat()
    const result = await sendMessage('app-1', 'halo')

    expect(api.post).toHaveBeenCalledWith('/applications/app-1/messages', { content: 'halo' })
    expect(result).toEqual(created)
  })

  it('sendMessage returns null and sets error on failure', async () => {
    ;(api.post as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: { data: { error: { message: 'Gagal mengirim pesan.' } } },
    })

    const { sendMessage, error } = useChat()
    const result = await sendMessage('app-1', 'halo')

    expect(result).toBeNull()
    expect(error.value).toBe('Gagal mengirim pesan.')
  })
})
