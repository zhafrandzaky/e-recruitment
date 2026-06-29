<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Eye, EyeOff, CheckCircle, KeyRound } from 'lucide-vue-next'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { useAuthStore } from '../../stores/auth'
import type { AxiosError } from 'axios'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const token = (route.query.token as string) ?? ''
const emailFromQuery = (route.query.email as string) ?? ''

const form = reactive({ email: emailFromQuery, password: '', passwordConfirmation: '' })
const showPassword = ref(false)
const isSubmitting = ref(false)
const isSuccess = ref(false)
const errorMessage = ref('')

async function handleSubmit() {
  errorMessage.value = ''
  isSubmitting.value = true

  try {
    await auth.resetPassword(token, form.email, form.password, form.passwordConfirmation)
    isSuccess.value = true
    setTimeout(() => router.push('/login'), 2500)
  } catch (err: unknown) {
    const axiosErr = err as AxiosError<{ error: { message: string } }>
    errorMessage.value = axiosErr.response?.data?.error?.message ?? 'Gagal mereset password. Link mungkin sudah kadaluarsa.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="w-full max-w-md">
      <div
        class="rounded-xl border p-8"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <!-- Success -->
        <div v-if="isSuccess" class="text-center py-4">
          <CheckCircle :size="48" class="mx-auto mb-4" style="color: var(--color-accent)" />
          <h2 class="text-xl font-bold mb-2" style="color: var(--color-text-primary)">Password Diperbarui</h2>
          <p class="text-sm" style="color: var(--color-text-secondary)">
            Mengalihkan ke halaman login...
          </p>
        </div>

        <template v-else>
          <div class="mb-6">
            <KeyRound :size="32" class="mb-3" style="color: var(--color-primary)" />
            <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Buat Password Baru</h1>
            <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
              Masukkan password baru untuk akun Anda.
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
            <!-- Email (pre-filled from query, readonly) -->
            <div class="mb-4">
              <label for="email" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
                Email
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="email"
                class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              />
            </div>

            <!-- New password -->
            <div class="mb-4">
              <label for="password" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
                Password Baru
              </label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="new-password"
                  required
                  minlength="8"
                  placeholder="Min. 8 karakter"
                  class="w-full px-3 py-2.5 pr-10 rounded-lg text-sm outline-none"
                  style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
                />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary)">
                  <EyeOff v-if="showPassword" :size="16" />
                  <Eye v-else :size="16" />
                </button>
              </div>
            </div>

            <!-- Confirm password -->
            <div class="mb-6">
              <label for="passwordConfirmation" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
                Konfirmasi Password
              </label>
              <input
                id="passwordConfirmation"
                v-model="form.passwordConfirmation"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="new-password"
                required
                placeholder="Ulangi password baru"
                class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
                style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              />
            </div>

            <button
              type="submit"
              :disabled="isSubmitting || !form.password || !form.passwordConfirmation"
              class="w-full flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition-all"
              style="background: var(--color-primary); color: #ffffff"
              :class="{ 'opacity-60 cursor-not-allowed': isSubmitting || !form.password || !form.passwordConfirmation }"
            >
              <span v-if="isSubmitting" class="inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
              {{ isSubmitting ? 'Memproses...' : 'Simpan Password Baru' }}
            </button>
          </form>
        </template>
      </div>
    </div>
  </AuthLayout>
</template>
