import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import ChatThread from '../../src/components/ChatThread.vue'
import type { ChatMessage } from '../../src/types'

/**
 * Frontend E2E for real-time chat (docs/TESTING.md Section 4): a message sent
 * by the other party appears for this party WITHOUT a page reload.
 *
 * `@laravel/echo-vue`'s `useEcho` is mocked to capture the listener so the test
 * can deliver an inbound socket event the way Reverb would, then assert the DOM
 * updates with no refetch.
 */
let echoCallback: ((payload: ChatMessage) => void) | null = null

vi.mock('@laravel/echo-vue', () => ({
  useEcho: (_channel: string, _event: string, cb: (payload: ChatMessage) => void) => {
    echoCallback = cb
    return {
      leaveChannel: vi.fn(),
      leave: vi.fn(),
      stopListening: vi.fn(),
      listen: vi.fn(),
      channel: vi.fn(),
    }
  },
}))

vi.mock('../../src/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

import api from '../../src/composables/useApi'

const APP_ID = 'application-uuid-1'
const ME = { id: 'me-uuid', name: 'Pelamar', email: 'me@test.com', role: 'applicant' as const }

function authenticateAs(user: typeof ME): void {
  localStorage.setItem('auth_token', 'test-token')
  localStorage.setItem('auth_user', JSON.stringify(user))
}

function mountChat() {
  return mount(ChatThread, {
    props: { applicationId: APP_ID, counterpartName: 'Tim Rekrutmen' },
    global: { plugins: [createPinia()] },
  })
}

describe('ChatThread real-time delivery', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
    echoCallback = null
  })

  it('renders an inbound message from the other party without a page reload', async () => {
    authenticateAs(ME)
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [] } })

    const wrapper = mountChat()
    await flushPromises()

    // History loaded once; message not present yet.
    expect(api.get).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).not.toContain('Halo, ada pertanyaan?')
    expect(echoCallback).not.toBeNull()

    // Simulate Reverb pushing a message sent by HR (the other party).
    echoCallback!({
      id: 'msg-from-hr',
      content: 'Halo, ada pertanyaan?',
      sender_id: 'hr-uuid',
      sent_at: new Date().toISOString(),
      sender: { id: 'hr-uuid', name: 'Tim HR' },
    })
    await flushPromises()

    // Appears in the DOM with no additional fetch (no reload/refetch).
    expect(wrapper.text()).toContain('Halo, ada pertanyaan?')
    expect(wrapper.findAll('.message')).toHaveLength(1)
    expect(api.get).toHaveBeenCalledTimes(1)

    // Rendered as the other party's message (left side, sender label shown).
    const bubble = wrapper.find('.message')
    expect(bubble.classes()).not.toContain('message--mine')
    expect(bubble.text()).toContain('Tim HR')
  })

  it('sending a message shows it as mine and de-dupes its own broadcast echo', async () => {
    authenticateAs(ME)
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: { data: [] } })
    ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: {
        id: 'msg-mine',
        content: 'Terima kasih',
        sender_id: ME.id,
        sent_at: new Date().toISOString(),
        sender: { id: ME.id, name: ME.name },
      },
    })

    const wrapper = mountChat()
    await flushPromises()

    await wrapper.find('.chat__input').setValue('Terima kasih')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(api.post).toHaveBeenCalledWith(`/applications/${APP_ID}/messages`, { content: 'Terima kasih' })
    expect(wrapper.findAll('.message')).toHaveLength(1)
    const mine = wrapper.find('.message')
    expect(mine.classes()).toContain('message--mine')

    // The same message also arrives over the socket (the sender receives their
    // own broadcast) — it must NOT be duplicated.
    echoCallback!({
      id: 'msg-mine',
      content: 'Terima kasih',
      sender_id: ME.id,
      sent_at: new Date().toISOString(),
      sender: { id: ME.id, name: ME.name },
    })
    await flushPromises()

    expect(wrapper.findAll('.message')).toHaveLength(1)
  })

  it('shows existing history on mount', async () => {
    authenticateAs(ME)
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({
      data: {
        data: [
          {
            id: 'h1',
            content: 'Pesan lama',
            sender_id: ME.id,
            sent_at: new Date().toISOString(),
            sender: { id: ME.id, name: ME.name },
          },
        ],
      },
    })

    const wrapper = mountChat()
    await flushPromises()

    expect(wrapper.text()).toContain('Pesan lama')
    expect(wrapper.findAll('.message')).toHaveLength(1)
  })
})
