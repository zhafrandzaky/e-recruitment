<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, EyeOff, UserPlus } from 'lucide-vue-next'
import AuthLayout from '../../layouts/AuthLayout.vue'
import { useAuthStore } from '../../stores/auth'
import { useTheme } from '../../composables/useTheme'
import type { AxiosError } from 'axios'

const router = useRouter()
const auth = useAuthStore()
const { theme } = useTheme()

const form = reactive({ name: '', email: '', password: '', passwordConfirmation: '' })
const showPassword = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

async function handleSubmit() {
  errorMessage.value = ''
  fieldErrors.value = {}
  isSubmitting.value = true

  try {
    await auth.register(form.name, form.email, form.password, form.passwordConfirmation)
    router.push('/jobs')
  } catch (err: unknown) {
    const axiosErr = err as AxiosError<{ error?: { message: string }; errors?: Record<string, string[]> }>
    if (axiosErr.response?.data?.errors) {
      fieldErrors.value = axiosErr.response.data.errors
    } else {
      errorMessage.value = axiosErr.response?.data?.error?.message ?? 'Pendaftaran gagal. Silakan coba lagi.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <img
          :src="theme === 'dark' ? '/src/assets/logo/logo-light.svg' : '/src/assets/logo/logo-primary.svg'"
          alt="Logo"
          class="h-10 w-auto mx-auto mb-4"
        />
        <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Buat Akun Baru</h1>
        <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
          Daftar sebagai pelamar untuk mulai melamar pekerjaan
        </p>
      </div>

      <div
        class="rounded-xl border p-8"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <!-- Error -->
        <Transition
          enter-from-class="opacity-0 -translate-y-1"
          enter-active-class="transition-all duration-200"
          leave-active-class="transition-all duration-200"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <div
            v-if="errorMessage"
            class="rounded-lg px-4 py-3 mb-6 text-sm"
            style="background: color-mix(in srgb, var(--color-error) 10%, transparent); color: var(--color-error); border: 1px solid color-mix(in srgb, var(--color-error) 25%, transparent)"
          >
            {{ errorMessage }}
          </div>
        </Transition>

        <form @submit.prevent="handleSubmit" novalidate class="space-y-4">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
              Nama Lengkap
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              placeholder="Nama lengkap Anda"
              class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
              style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              :disabled="isSubmitting"
            />
            <p v-if="fieldErrors.name" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div>
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
              class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
              style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              :disabled="isSubmitting"
            />
            <p v-if="fieldErrors.email" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.email[0] }}</p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
              Password
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
                :disabled="isSubmitting"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2"
                style="color: var(--color-text-secondary)"
                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
              >
                <EyeOff v-if="showPassword" :size="16" />
                <Eye v-else :size="16" />
              </button>
            </div>
            <p v-if="fieldErrors.password" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.password[0] }}</p>
          </div>

          <!-- Confirm password -->
          <div>
            <label for="passwordConfirmation" class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">
              Konfirmasi Password
            </label>
            <input
              id="passwordConfirmation"
              v-model="form.passwordConfirmation"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="new-password"
              required
              placeholder="Ulangi password Anda"
              class="w-full px-3 py-2.5 rounded-lg text-sm outline-none"
              style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)"
              :disabled="isSubmitting"
            />
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="isSubmitting || !form.name || !form.email || !form.password || !form.passwordConfirmation"
            class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all duration-200 mt-2"
            style="background: var(--color-primary); color: #ffffff"
            :class="{ 'opacity-60 cursor-not-allowed': isSubmitting || !form.name || !form.email || !form.password || !form.passwordConfirmation }"
          >
            <span v-if="isSubmitting" class="inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
            <UserPlus v-else :size="16" />
            {{ isSubmitting ? 'Mendaftar...' : 'Daftar Sekarang' }}
          </button>
        </form>
      </div>

      <p class="text-center text-sm mt-6" style="color: var(--color-text-secondary)">
        Sudah punya akun?
        <RouterLink to="/login" class="font-medium transition-colors" style="color: var(--color-primary)">
          Masuk
        </RouterLink>
      </p>
    </div>
  </AuthLayout>
</template>
