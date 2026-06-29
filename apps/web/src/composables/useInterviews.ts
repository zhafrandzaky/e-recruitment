import { ref } from 'vue'
import api from './useApi'
import type { Interview } from '../types'

export function useInterviews() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchInterview(applicationId: string): Promise<Interview | null> {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/applications/${applicationId}/interview`)
      return response.data as Interview
    } catch (e: any) {
      if (e.response?.status === 404) {
        return null
      }
      error.value = e.response?.data?.error?.message ?? 'Gagal memuat data interview.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function scheduleInterview(applicationId: string, scheduledAt: string, meetingLink: string): Promise<Interview | null> {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/applications/${applicationId}/interview`, {
        scheduled_at: scheduledAt,
        meeting_link: meetingLink,
      })
      return response.data as Interview
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Gagal menjadwalkan interview.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function rescheduleInterview(applicationId: string, scheduledAt: string, meetingLink?: string): Promise<Interview | null> {
    loading.value = true
    error.value = null

    try {
      const payload: Record<string, string> = { scheduled_at: scheduledAt }
      if (meetingLink) {
        payload.meeting_link = meetingLink
      }
      const response = await api.patch(`/applications/${applicationId}/interview`, payload)
      return response.data as Interview
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Gagal mengubah jadwal interview.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function cancelInterview(applicationId: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      await api.delete(`/applications/${applicationId}/interview`)
      return true
    } catch (e: any) {
      error.value = e.response?.data?.error?.message ?? 'Gagal membatalkan interview.'
      return false
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    fetchInterview,
    scheduleInterview,
    rescheduleInterview,
    cancelInterview,
  }
}
