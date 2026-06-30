<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../composables/useApi'
import CvUploader from '../../components/CvUploader.vue'
import StatusBadge from '../../components/StatusBadge.vue'
import { ArrowLeft, Send, CheckCircle2, AlertCircle } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import type { JobPosting, Application } from '../../types'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const job = ref<JobPosting | null>(null)
const loading = ref(true)
const submitting = ref(false)
const success = ref(false)
const submittedApplication = ref<Application | null>(null)

// Form fields — per FR-008
const form = ref({
  name: auth.user?.name ?? '',
  phone: '',
  address: '',
})

const cvFile = ref<File | null>(null)
const cvError = ref<string | null>(null)
const serverErrors = ref<Record<string, string>>({})

const isFormValid = computed(() => {
  return (
    form.value.name.trim() !== '' &&
    form.value.phone.trim() !== '' &&
    form.value.address.trim() !== '' &&
    cvFile.value !== null &&
    cvError.value === null
  )
})

function onFileSelected(file: File) {
  cvFile.value = file

  const ext = file.name.split('.').pop()?.toLowerCase()
  if (ext !== 'pdf') {
    cvError.value = 'Format file tidak didukung. Hanya file PDF yang diterima.'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    cvError.value = 'Ukuran file melebihi batas maksimum 2MB.'
    return
  }
  cvError.value = null
}

function onFileCleared() {
  cvFile.value = null
  cvError.value = null
}

async function fetchJob() {
  try {
    const { data } = await api.get(`/jobs/${route.params.id}`)
    job.value = data
  } catch {
    router.replace({ name: 'not-found' })
  } finally {
    loading.value = false
  }
}

async function submitApplication() {
  if (!isFormValid.value) return
  submitting.value = true
  serverErrors.value = {}

  const formData = new FormData()
  formData.append('name', form.value.name)
  formData.append('phone', form.value.phone)
  formData.append('address', form.value.address)
  if (cvFile.value) {
    formData.append('cv', cvFile.value)
  }

  try {
    const { data } = await api.post(`/jobs/${route.params.id}/applications`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    submittedApplication.value = data
    success.value = true
  } catch (err: any) {
    if (err.response?.status === 422) {
      const fields = err.response.data?.error?.fields
      if (fields) {
        const mapped: Record<string, string> = {}
        for (const [key, messages] of Object.entries(fields)) {
          mapped[key] = Array.isArray(messages) ? messages[0] : String(messages)
        }
        serverErrors.value = mapped
      }
    }
  } finally {
    submitting.value = false
  }
}

fetchJob()
</script>

<template>
  <AppLayout>
  <div class="apply-page">
    <div v-if="loading" class="apply-page__loading">Memuat lowongan...</div>

    <template v-else-if="job">
      <!-- Back link -->
      <button class="back-link" @click="router.back()">
        <ArrowLeft :size="18" />
        <span>Kembali ke detail lowongan</span>
      </button>

      <!-- Success state -->
      <div v-if="success && submittedApplication" class="success-card">
        <div class="success-card__icon">
          <CheckCircle2 :size="48" />
        </div>
        <h2 class="success-card__title">Lamaran Terkirim!</h2>
        <p class="success-card__text">
          Lamaran Anda untuk posisi <strong>{{ job.title }}</strong> telah berhasil dikirim.
          Status lamaran Anda saat ini:
        </p>
        <StatusBadge :status="submittedApplication.status" class="success-card__badge" />
        <p class="success-card__hint">
          Anda akan menerima notifikasi email saat status lamaran berubah. Anda juga dapat
          memantau status lamaran di halaman
          <router-link :to="{ name: 'my-applications' }" class="success-card__link">Lamaran Saya</router-link>.
        </p>
        <button class="btn btn--primary" @click="router.push({ name: 'jobs' })">
          Lihat Lowongan Lain
        </button>
      </div>

      <!-- Application form -->
      <div v-else class="form-card">
        <h1 class="form-card__title">Lamar Posisi: {{ job.title }}</h1>
        <p class="form-card__subtitle">
          {{ job.location }} &middot; Batas akhir: {{ job.deadline ?? 'Tidak ditentukan' }}
        </p>

        <!-- Auth check -->
        <div v-if="!auth.isAuthenticated" class="auth-notice">
          <AlertCircle :size="18" />
          <span>
            Anda harus <router-link :to="{ name: 'login', query: { redirect: route.fullPath } }">login</router-link>
            terlebih dahulu untuk melamar.
          </span>
        </div>

        <form v-else @submit.prevent="submitApplication" class="apply-form" novalidate>
          <!-- Name -->
          <div class="form-group">
            <label for="name" class="form-label">Nama Lengkap <span class="required">*</span></label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="form-input"
              :class="{ 'form-input--error': serverErrors.name }"
              placeholder="Nama lengkap Anda"
              required
            />
            <p v-if="serverErrors.name" class="form-error">{{ serverErrors.name }}</p>
          </div>

          <!-- Phone -->
          <div class="form-group">
            <label for="phone" class="form-label">Nomor HP <span class="required">*</span></label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="form-input"
              :class="{ 'form-input--error': serverErrors.phone }"
              placeholder="0812-3456-7890"
              required
            />
            <p v-if="serverErrors.phone" class="form-error">{{ serverErrors.phone }}</p>
          </div>

          <!-- Address -->
          <div class="form-group">
            <label for="address" class="form-label">Alamat <span class="required">*</span></label>
            <textarea
              id="address"
              v-model="form.address"
              class="form-input form-input--textarea"
              :class="{ 'form-input--error': serverErrors.address }"
              rows="3"
              placeholder="Alamat lengkap Anda"
              required
            ></textarea>
            <p v-if="serverErrors.address" class="form-error">{{ serverErrors.address }}</p>
          </div>

          <!-- CV Upload -->
          <div class="form-group">
            <CvUploader
              :error="cvError ?? serverErrors.cv"
              @file-selected="onFileSelected"
              @file-cleared="onFileCleared"
            />
          </div>

          <!-- Submit -->
          <button
            type="submit"
            class="btn btn--primary btn--submit"
            :disabled="!isFormValid || submitting"
          >
            <Send v-if="!submitting" :size="18" />
            <span v-if="submitting">Mengirim Lamaran...</span>
            <span v-else>Kirim Lamaran</span>
          </button>

          <p v-if="!isFormValid && !submitting" class="form-hint">
            Lengkapi semua field wajib dan unggah CV (PDF, maks 2MB) untuk mengirim lamaran.
          </p>
        </form>
      </div>
    </template>
  </div>
  </AppLayout>
</template>

<style scoped>
.apply-page {
  max-width: 640px;
  margin: 0 auto;
  padding: 24px 16px 64px;
}

.apply-page__loading {
  text-align: center;
  padding: 64px 0;
  color: var(--color-text-secondary);
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 20px;
  background: none;
  border: none;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  cursor: pointer;
  padding: 0;
}

.back-link:hover {
  color: var(--color-primary);
}

/* Success */
.success-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 40px 32px;
  text-align: center;
}

.success-card__icon {
  color: var(--color-accent);
  margin-bottom: 16px;
}

.success-card__title {
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 12px;
}

.success-card__text {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
  margin: 0 0 12px;
  line-height: 1.6;
}

.success-card__badge {
  margin-bottom: 16px;
  font-size: 0.875rem;
}

.success-card__hint {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  margin: 0 0 24px;
}

.success-card__link {
  color: var(--color-primary);
  font-weight: 500;
}

/* Form */
.form-card {
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 32px;
}

.form-card__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 4px;
}

.form-card__subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0 0 24px;
}

.auth-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  background: #fef3c7;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #92400e;
  margin-bottom: 20px;
}

.auth-notice a {
  color: var(--color-primary);
  font-weight: 500;
}

.apply-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-primary);
}

.required {
  color: var(--color-error);
}

.form-input {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.9375rem;
  color: var(--color-text-primary);
  background: var(--color-background);
  transition: border-color 0.15s;
  font-family: inherit;
}

.form-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-subtle);
}

.form-input--error {
  border-color: var(--color-error);
}

.form-input--textarea {
  resize: vertical;
}

.form-error {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--color-error);
}

.btn--submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  font-size: 0.9375rem;
  font-weight: 600;
  margin-top: 4px;
}

.btn--submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.form-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  text-align: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: background-color 0.15s;
  font-family: inherit;
}

.btn--primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn--primary:hover {
  background: var(--color-primary-hover);
}
</style>
