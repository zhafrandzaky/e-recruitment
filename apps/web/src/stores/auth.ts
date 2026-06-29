import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '../types'
import api from '../composables/useApi'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const user = ref<User | null>(JSON.parse(localStorage.getItem('auth_user') ?? 'null'))

  const isAuthenticated = computed(() => token.value !== null && user.value !== null)
  const isHrAdmin = computed(() => user.value?.role === 'hr_admin')
  const isApplicant = computed(() => user.value?.role === 'applicant')

  function setAuth(newToken: string, newUser: User): void {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('auth_token', newToken)
    localStorage.setItem('auth_user', JSON.stringify(newUser))
  }

  function clearAuth(): void {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  async function login(email: string, password: string): Promise<void> {
    const { data } = await api.post('/auth/login', { email, password })
    setAuth(data.token, data.user)
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string): Promise<void> {
    const { data } = await api.post('/auth/register', {
      name,
      email,
      password,
      password_confirmation: passwordConfirmation,
    })
    setAuth(data.token, data.user)
  }

  async function logout(): Promise<void> {
    try {
      await api.post('/auth/logout')
    } finally {
      clearAuth()
    }
  }

  async function forgotPassword(email: string): Promise<string> {
    const { data } = await api.post('/auth/forgot-password', { email })
    return data.message
  }

  async function resetPassword(token: string, email: string, password: string, passwordConfirmation: string): Promise<string> {
    const { data } = await api.post('/auth/reset-password', {
      token,
      email,
      password,
      password_confirmation: passwordConfirmation,
    })
    return data.message
  }

  return {
    token,
    user,
    isAuthenticated,
    isHrAdmin,
    isApplicant,
    login,
    register,
    logout,
    forgotPassword,
    resetPassword,
    clearAuth,
  }
})
