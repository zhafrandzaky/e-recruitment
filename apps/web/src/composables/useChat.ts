import { ref } from 'vue'
import api from './useApi'
import type { ChatMessage } from '../types'

/**
 * REST access to the per-application chat thread (FR-017).
 * Real-time delivery is handled separately by `useEcho` in ChatThread.vue;
 * this composable owns history loading and sending.
 */
export function useChat() {
  const loading = ref(false)
  const sending = ref(false)
  const error = ref<string | null>(null)

  async function fetchMessages(applicationId: string): Promise<ChatMessage[]> {
    loading.value = true
    error.value = null

    try {
      const { data } = await api.get(`/applications/${applicationId}/messages`)
      return data.data as ChatMessage[]
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Gagal memuat pesan.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function sendMessage(applicationId: string, content: string): Promise<ChatMessage | null> {
    sending.value = true
    error.value = null

    try {
      const { data } = await api.post(`/applications/${applicationId}/messages`, { content })
      return data as ChatMessage
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Gagal mengirim pesan.'
      return null
    } finally {
      sending.value = false
    }
  }

  return { loading, sending, error, fetchMessages, sendMessage }
}
