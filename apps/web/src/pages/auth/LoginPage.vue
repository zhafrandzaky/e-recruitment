<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Eye, EyeOff, LogIn } from 'lucide-vue-next'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { useAuthStore } from '../../stores/auth'
import { useTheme } from '../../composables/useTheme'
import type { AxiosError } from 'axios'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const { theme } = useTheme()

const form = reactive({ email: '', password: '' })
const showPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const lockoutSeconds = ref<number | null>(null)

async function handleSubmit() {
  errorMessage.value = ''
  lockoutSeconds.value = null
  isSubmitting.value = true

  try {
    await auth.login(form.email, form.password)
    const redirect = (route.query.redirect as string) ?? (auth.isHrAdmin ? '/hr/jobs' : '/jobs')
    router.push(redirect)
  } catch (err: unknown) {
    const axiosErr = err as AxiosError<{ error: { code: string; message: string; retry_after_seconds?: number } }>
    const apiError = axiosErr.response?.data?.error

    if (axiosErr.response?.status === 423 && apiError) {
      errorMessage.value = apiError.message
      lockoutSeconds.value = apiError.retry_after_seconds ?? null
    } else if (apiError) {
      errorMessage.value = apiError.message
    } else {
      errorMessage.value = 'Terjadi kesalahan. Silakan coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="w-full max-w-md">
      <!-- Logo + title -->
      <div class="text-center mb-8">
        <img
          :src="theme === 'dark' ? '/src/assets/logo/logo-light.svg' : '/src/assets/logo/logo-primary.svg'"
          alt="Logo"
          class="h-10 w-auto mx-auto mb-4"
        />
        <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Masuk ke Akun</h1>
        <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
          Masukkan email dan password Anda
        </p>
      </div>

      <!-- Card -->
      <div
        class="rounded-xl border p-8"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <!-- Error message -->
        <Transition enter-from-class="opacity-0 -translate-y-1" enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-200" leave-to-class="opacity-0 -translate-y-1">
          <div
            v-if="errorMessage"
            class="rounded-lg px-4 py-3 mb-6 text-sm"
            style="background: color-mix(in srgb, var(--color-error) 10%, transparent); color: var(--color-error); border: 1px solid color-mix(in srgb, var(--color-error) 25%, transparent)"
          >
            {{ errorMessage }}
          </div>
        </Transition>

        <form @submit.prevent="handleSubmit" novalidate>
          <!-- Email -->
          <div class="mb-4">
            <label for="email" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
              Email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
              placeholder="email@contoh.com"
              class="w-full px-3 py-2.5 rounded-lg text-sm outline-none transition-shadow"
              style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              :class="{ 'opacity-60': isSubmitting }"
              :disabled="isSubmitting"
            />
          </div>

          <!-- Password -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-1.5">
              <label for="password" class="block text-sm font-medium" style="color: var(--color-text-primary)">
                Password
              </label>
              <RouterLink
                to="/forgot-password"
                class="text-xs font-medium transition-colors"
                style="color: var(--color-primary)"
              >
                Lupa password?
              </RouterLink>
            </div>
            <div class="relative">
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="w-full px-3 py-2.5 pr-10 rounded-lg text-sm outline-none transition-shadow"
                style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
                :disabled="isSubmitting"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors"
                style="color: var(--color-text-secondary)"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
              >
                <EyeOff v-if="showPassword" :size="16" />
                <Eye v-else :size="16" />
              </button>
            </div>
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="isSubmitting || !form.email || !form.password"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-200"
            style="background: var(--color-primary); color: #ffffff"
            :class="{ 'opacity-60 cursor-not-allowed': isSubmitting || !form.email || !form.password }"
          >
            <LogIn v-if="!isSubmitting" :size="16" />
            <span v-if="isSubmitting" class="inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
            {{ isSubmitting ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>
      </div>

      <!-- Register link -->
      <p class="text-center text-sm mt-6" style="color: var(--color-text-secondary)">
        Belum punya akun?
        <RouterLink to="/register" class="font-medium transition-colors" style="color: var(--color-primary)">
          Daftar sekarang
        </RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>
