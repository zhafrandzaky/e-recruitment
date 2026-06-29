import { describe, it, expect, beforeEach, vi } from 'vitest'

vi.mock('../../src/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

import api from '../../src/composables/useApi'
import { useReports } from '../../src/composables/useReports'
import type { ReportOverview, JobFunnel } from '../../src/types'

const overviewFixture: ReportOverview = {
  applicants_per_job: [
    { job_id: 'job-a', job_title: 'Job A', count: 3 },
    { job_id: 'job-b', job_title: 'Job B', count: 0 },
  ],
  funnel: { pending: 4, shortlisted: 3, rejected: 2, hired: 1 },
  avg_time_to_hire_days: 15,
}

describe('useReports', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('fetchOverview stores the overview payload on success', async () => {
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: overviewFixture })

    const { overview, fetchOverview, error } = useReports()
    await fetchOverview()

    expect(api.get).toHaveBeenCalledWith('/reports/overview')
    expect(overview.value).toEqual(overviewFixture)
    expect(error.value).toBeNull()
  })

  it('fetchOverview sets an error message on failure', async () => {
    ;(api.get as ReturnType<typeof vi.fn>).mockRejectedValue(new Error('boom'))

    const { overview, fetchOverview, error } = useReports()
    await fetchOverview()

    expect(overview.value).toBeNull()
    expect(error.value).toBe('Gagal memuat data laporan.')
  })

  it('fetchJobFunnel returns the per-job funnel payload', async () => {
    const jobFunnel: JobFunnel = {
      job_id: 'job-a',
      job_title: 'Job A',
      funnel: { pending: 2, shortlisted: 0, rejected: 0, hired: 1 },
      total: 3,
    }
    ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValue({ data: jobFunnel })

    const { fetchJobFunnel } = useReports()
    const result = await fetchJobFunnel('job-a')

    expect(api.get).toHaveBeenCalledWith('/reports/jobs/job-a/funnel')
    expect(result).toEqual(jobFunnel)
  })
})
