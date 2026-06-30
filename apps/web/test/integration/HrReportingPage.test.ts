import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import type { ReportOverview, JobFunnel } from '../../src/types'

const overviewFixture: ReportOverview = {
  applicants_per_job: [
    { job_id: 'job-a', job_title: 'Backend Engineer', count: 5 },
    { job_id: 'job-b', job_title: 'Designer', count: 0 },
  ],
  funnel: { pending: 4, shortlisted: 3, rejected: 2, hired: 1 },
  avg_time_to_hire_days: 15,
}

const jobFunnelFixture: JobFunnel = {
  job_id: 'job-a',
  job_title: 'Backend Engineer',
  funnel: { pending: 2, shortlisted: 1, rejected: 0, hired: 2 },
  total: 5,
}

const getMock = vi.fn()

vi.mock('../../src/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => getMock(...args),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

import HrReportingPage from '../../src/pages/hr/HrReportingPage.vue'

// HrReportingPage now wraps AppLayout, which uses Pinia (auth store) + the router.
const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: { template: '<div />' } },
    { path: '/jobs', component: { template: '<div />' } },
    { path: '/login', component: { template: '<div />' } },
    { path: '/applications/me', component: { template: '<div />' } },
    { path: '/hr/jobs', component: { template: '<div />' } },
    { path: '/hr/jobs/create', component: { template: '<div />' } },
    { path: '/hr/reports', component: { template: '<div />' } },
  ],
})

function mountPage() {
  return mount(HrReportingPage, {
    global: { plugins: [createPinia(), router] },
  })
}

function mockEndpoints() {
  getMock.mockImplementation((url: string) => {
    if (url === '/reports/overview') return Promise.resolve({ data: overviewFixture })
    if (url.startsWith('/reports/jobs/')) return Promise.resolve({ data: jobFunnelFixture })
    return Promise.reject(new Error(`unexpected url ${url}`))
  })
}

describe('HrReportingPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads the overview and renders the headline metrics', async () => {
    mockEndpoints()
    const wrapper = mountPage()
    await flushPromises()

    const text = wrapper.text()
    // Total applicants = 4+3+2+1 = 10
    expect(text).toContain('Total Pelamar')
    expect(text).toContain('10')
    // Time-to-hire figure
    expect(text).toContain('Rata-rata Waktu Perekrutan')
    expect(text).toContain('15')
    // Applicants per job
    expect(text).toContain('Backend Engineer')
    expect(text).toContain('Designer')
  })

  it('renders the overall funnel with each stage count', async () => {
    mockEndpoints()
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.text()).toContain('Funnel Seleksi')
    expect(wrapper.text()).toContain('Diterima')
  })

  it('loads a per-job funnel when a job is selected (second endpoint)', async () => {
    mockEndpoints()
    const wrapper = mountPage()
    await flushPromises()

    const select = wrapper.find('.job-picker__select')
    await select.setValue('job-a')
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith('/reports/jobs/job-a/funnel')
    expect(wrapper.find('.job-funnel').exists()).toBe(true)
  })

  it('shows an error state when the overview request fails', async () => {
    getMock.mockRejectedValue(new Error('boom'))
    const wrapper = mountPage()
    await flushPromises()

    expect(wrapper.find('.state-error').exists()).toBe(true)
    expect(wrapper.text()).toContain('Gagal memuat data laporan.')
  })
})
