import { ref } from 'vue'
import api from './useApi'
import type { JobPosting, PaginatedResponse } from '../types'

export function useJobs() {
  const jobs = ref<JobPosting[]>([])
  const meta = ref({ page: 1, per_page: 15, total: 0 })
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchJobs(search = '', page = 1): Promise<void> {
    isLoading.value = true
    error.value = null
    try {
      const params: Record<string, unknown> = { page }
      if (search.trim()) params.search = search.trim()
      const { data } = await api.get<PaginatedResponse<JobPosting>>('/jobs', { params })
      jobs.value = data.data
      meta.value = data.meta
    } catch {
      error.value = 'Gagal memuat daftar lowongan.'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchJob(id: string): Promise<JobPosting> {
    const { data } = await api.get<JobPosting>(`/jobs/${id}`)
    return data
  }

  async function createJob(payload: Partial<JobPosting>): Promise<JobPosting> {
    const { data } = await api.post<JobPosting>('/jobs', payload)
    return data
  }

  async function updateJob(id: string, payload: Partial<JobPosting>): Promise<JobPosting> {
    const { data } = await api.put<JobPosting>(`/jobs/${id}`, payload)
    return data
  }

  async function updateJobStatus(id: string, status: string): Promise<void> {
    await api.patch(`/jobs/${id}/status`, { status })
  }

  async function deleteJob(id: string): Promise<void> {
    await api.delete(`/jobs/${id}`)
  }

  async function fetchAllHrJobs(page = 1): Promise<PaginatedResponse<JobPosting>> {
    const { data } = await api.get<PaginatedResponse<JobPosting>>('/jobs', { params: { page, all: true } })
    return data
  }

  return {
    jobs,
    meta,
    isLoading,
    error,
    fetchJobs,
    fetchJob,
    createJob,
    updateJob,
    updateJobStatus,
    deleteJob,
    fetchAllHrJobs,
  }
}
