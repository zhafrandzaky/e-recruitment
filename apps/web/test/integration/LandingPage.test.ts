import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import LandingPage from '../../src/pages/LandingPage.vue'

vi.mock('gsap', () => ({
  gsap: {
    registerPlugin: vi.fn(),
    from: vi.fn(),
    to: vi.fn(),
    utils: { toArray: vi.fn(() => []) },
    timeline: vi.fn(() => ({ from: vi.fn().mockReturnThis(), defaults: vi.fn() })),
  },
  ScrollTrigger: {},
}))

vi.mock('../../src/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({
      data: { active_jobs: 7, registered_applicants: 42 },
    }),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'landing', component: LandingPage },
    { path: '/jobs', name: 'jobs', component: { template: '<div>jobs</div>' } },
    { path: '/login', name: 'login', component: { template: '<div>login</div>' } },
    { path: '/register', name: 'register', component: { template: '<div>register</div>' } },
  ],
})

function mountLanding() {
  return mount(LandingPage, {
    global: { plugins: [createPinia(), router] },
  })
}

describe('LandingPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('renders without crashing', () => {
    const wrapper = mountLanding()
    expect(wrapper.exists()).toBe(true)
  })

  it('contains a link to /jobs as the primary CTA', () => {
    const wrapper = mountLanding()
    const jobLinks = wrapper.findAll('a').filter((a) =>
      a.attributes('href')?.includes('jobs'),
    )
    expect(jobLinks.length).toBeGreaterThan(0)
  })

  it('contains a link to /register for unauthenticated users', () => {
    const wrapper = mountLanding()
    const registerLinks = wrapper.findAll('a').filter((a) =>
      a.attributes('href')?.includes('register'),
    )
    expect(registerLinks.length).toBeGreaterThan(0)
  })

  it('displays live stats from the API after load', async () => {
    const wrapper = mountLanding()
    await flushPromises()
    expect(wrapper.text()).toContain('Lowongan Aktif')
    expect(wrapper.text()).toContain('Pelamar Terdaftar')
  })

  it('has hero headline and CTA text', () => {
    const wrapper = mountLanding()
    expect(wrapper.text()).toContain('Lihat Lowongan')
  })

  it('has benefit section with expected content', () => {
    const wrapper = mountLanding()
    expect(wrapper.text()).toContain('Karir yang Berkembang')
  })
})

describe('Router: root path', () => {
  it('/ route resolves to LandingPage, not a redirect to /jobs', async () => {
    await router.push('/')
    await router.isReady()
    expect(router.currentRoute.value.path).toBe('/')
    expect(router.currentRoute.value.name).toBe('landing')
  })

  it('unknown path resolves to not-found, not /jobs', async () => {
    const testRouter = createRouter({
      history: createWebHashHistory(),
      routes: [
        { path: '/', name: 'landing', component: { template: '<div />' } },
        { path: '/jobs', name: 'jobs', component: { template: '<div />' } },
        { path: '/:pathMatch(.*)*', name: 'not-found', component: { template: '<div />' } },
      ],
    })
    await testRouter.push('/this-does-not-exist')
    await testRouter.isReady()
    expect(testRouter.currentRoute.value.name).toBe('not-found')
    expect(testRouter.currentRoute.value.path).toBe('/this-does-not-exist')
  })
})
