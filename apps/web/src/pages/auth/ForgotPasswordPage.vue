<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Mail, ArrowLeft, CheckCircle } from 'lucide-vue-next'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { useAuthStore } from '../../stores/auth'
import type { AxiosError } from 'axios'

const auth = useAuthStore()

const form = reactive({ email: '' })
const isSubmitting = ref(false)
const isSubmitted = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await auth.forgotPassword(form.email)
    isSubmitted.value = true
  } catch (err: unknown) {
    const axiosErr = err as AxiosError<{ error: { message: string } }>
    errorMessage.value = axiosErr.response?.data?.error?.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="w-full max-w-md">
      <!-- Back link -->
      <RouterLink
        to="/login"
        class="inline-flex items-center gap-1.5 text-sm mb-6 transition-colors"
        style="color: var(--color-text-secondary)"
      >
        <ArrowLeft :size="16" />
        Kembali ke Login
      </RouterLink>

      <div
        class="rounded-xl border p-8"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <!-- Success state -->
        <Transition enter-from-class="opacity-0 scale-95" enter-active-class="transition-all duration-300" appear>
          <div v-if="isSubmitted" class="text-center py-4">
            <CheckCircle :size="48" class="mx-auto mb-4" style="color: var(--color-accent)" />
            <h2 class="text-xl font-bold mb-2" style="color: var(--color-text-primary)">Email Terkirim</h2>
            <p class="text-sm" style="color: var(--color-text-secondary)">
              Jika email terdaftar, link reset password akan dikirimkan. Periksa kotak masuk Anda.
            </p>
          </div>
        </Transition>

        <!-- Form state -->
        <template v-if="!isSubmitted">
          <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Lupa Password</h1>
            <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
              Masukkan email akun Anda dan kami akan mengirimkan link reset.
            </p>
          </div>

          <!-- Error -->
          <div
            v-if="errorMessage"
            class="rounded-lg px-4 py-3 mb-6 text-sm"
            style="background: color-mix(in srgb, var(--color-error) 10%, transparent); color: var(--color-error); border: 1px solid color-mix(in srgb, var(--color-error) 25%, transparent)"
          >
            {{ errorMessage }}
          </div>

          <form @submit.prevent="handleSubmit" novalidate>
            <div class="mb-6">
              <label for="email" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
                Email
              </label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary)">
                  <Mail :size="16" />
                </span>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  autocomplete="email"
                  required
                  placeholder="email@contoh.com"
                  class="w-full pl-9 pr-3 py-2.5 rounded-lg text-sm outline-none"
                  style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
                  :disabled="isSubmitting"
                />
              </div>
            </div>

            <button
              type="submit"
              :disabled="isSubmitting || !form.email"
              class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all"
              style="background: var(--color-primary); color: #ffffff"
              :class="{ 'opacity-60 cursor-not-allowed': isSubmitting || !form.email }"
            >
              <span v-if="isSubmitting" class="inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
              {{ isSubmitting ? 'Mengirim...' : 'Kirim Link Reset' }}
            </button>
          </form>
        </template>
      </div>
    </div>
  </AuthLayout>
</template>
