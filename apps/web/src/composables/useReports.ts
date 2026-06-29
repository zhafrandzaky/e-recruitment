import { ref } from 'vue'
import api from './useApi'
import type { ReportOverview, JobFunnel } from '../types'

/**
 * HR reporting dashboard data (FR-018).
 * Both endpoints are HR-only and authorized server-side via the bearer token
 * already applied by the shared axios instance (useApi).
 */
export function useReports() {
  const overview = ref<ReportOverview | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchOverview(): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const { data } = await api.get<ReportOverview>('/reports/overview')
      overview.value = data
    } catch {
      error.value = 'Gagal memuat data laporan.'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchJobFunnel(jobId: string): Promise<JobFunnel> {
    const { data } = await api.get<JobFunnel>(`/reports/jobs/${jobId}/funnel`)
    return data
  }

  return {
    overview,
    isLoading,
    error,
    fetchOverview,
    fetchJobFunnel,
  }
}
