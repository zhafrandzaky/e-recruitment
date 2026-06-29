import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import LoginPage from '../../src/pages/auth/LoginPage.vue'
import { useAuthStore } from '../../src/stores/auth'

vi.mock('../../src/composables/useApi', () => ({
  default: {
    post: vi.fn(),
    interceptors: { request: { use: vi.fn() }, response: { use: vi.fn() } },
  },
}))

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: { template: '<div>home</div>' } },
    { path: '/login', component: LoginPage },
    { path: '/jobs', component: { template: '<div>jobs</div>' } },
    { path: '/hr/jobs', component: { template: '<div>hr</div>' } },
    { path: '/forgot-password', component: { template: '<div>forgot</div>' } },
    { path: '/register', component: { template: '<div>register</div>' } },
  ],
})

function mountLoginPage() {
  return mount(LoginPage, {
    global: {
      plugins: [createPinia(), router],
    },
  })
}

describe('LoginPage', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('renders email and password fields', () => {
    const wrapper = mountLoginPage()
    expect(wrapper.find('#email').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
  })

  it('submit button is disabled when fields are empty', () => {
    const wrapper = mountLoginPage()
    const btn = wrapper.find('button[type="submit"]')
    expect((btn.element as HTMLButtonElement).disabled).toBe(true)
  })

  it('submit button enables when both fields filled', async () => {
    const wrapper = mountLoginPage()
    await wrapper.find('#email').setValue('user@test.com')
    await wrapper.find('#password').setValue('password')
    const btn = wrapper.find('button[type="submit"]')
    expect((btn.element as HTMLButtonElement).disabled).toBe(false)
  })

  it('shows error message on failed login', async () => {
    const api = await import('../../src/composables/useApi')
    ;(api.default.post as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: {
        status: 401,
        data: { error: { code: 'INVALID_CREDENTIALS', message: 'Email atau password salah.' } },
      },
    })

    const wrapper = mountLoginPage()
    await wrapper.find('#email').setValue('wrong@test.com')
    await wrapper.find('#password').setValue('wrongpass')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Email atau password salah')
  })

  it('shows lockout message on 423 response', async () => {
    const api = await import('../../src/composables/useApi')
    ;(api.default.post as ReturnType<typeof vi.fn>).mockRejectedValue({
      response: {
        status: 423,
        data: { error: { code: 'ACCOUNT_LOCKED', message: 'Akun Anda terkunci sementara.', retry_after_seconds: 900 } },
      },
    })

    const wrapper = mountLoginPage()
    await wrapper.find('#email').setValue('locked@test.com')
    await wrapper.find('#password').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('terkunci')
  })

  it('has a link to the forgot password page', () => {
    const wrapper = mountLoginPage()
    const link = wrapper.find('a[href*="forgot-password"]')
    expect(link.exists()).toBe(true)
  })
})
