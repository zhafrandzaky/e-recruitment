<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../composables/useApi'
import StatusBadge from '../../components/StatusBadge.vue'
import InterviewScheduler from '../../components/InterviewScheduler.vue'
import ChatThread from '../../components/ChatThread.vue'
import {
  ArrowLeft,
  Download,
  FileText,
  AlertCircle,
  Loader2,
  Mail,
  Phone,
  MapPin,
} from 'lucide-vue-next'
import type { Application, ApplicationStatus } from '../../types'

const route = useRoute()
const router = useRouter()

const application = ref<Application | null>(null)
const loading = ref(true)
const cvLoading = ref(false)
const cvError = ref<string | null>(null)
const statusUpdating = ref(false)
const statusError = ref<string | null>(null)
const selectedStatus = ref<ApplicationStatus>('pending')

const statusOptions: { value: ApplicationStatus; label: string }[] = [
  { value: 'pending', label: 'Menunggu' },
  { value: 'shortlisted', label: 'Lolos Seleksi Berkas' },
  { value: 'rejected', label: 'Ditolak' },
  { value: 'hired', label: 'Diterima' },
]

onMounted(async () => {
  try {
    const { data } = await api.get(`/applications/${route.params.id}`)
    application.value = data
    selectedStatus.value = data.status
  } catch {
    router.replace({ name: 'not-found' })
  } finally {
    loading.value = false
  }
})

async function downloadCv() {
  if (!application.value) return
  cvLoading.value = true
  cvError.value = null

  try {
    const response = await api.get(`/applications/${application.value.id}/cv`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', application.value.cv_original_filename ?? 'cv.pdf')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err: any) {
    if (err.response?.data?.error?.message) {
      cvError.value = err.response.data.error.message
    } else {
      cvError.value = 'Gagal mengunduh CV. Silakan coba lagi.'
    }
  } finally {
    cvLoading.value = false
  }
}

async function updateStatus() {
  if (!application.value) return
  if (selectedStatus.value === application.value.status) return

  statusUpdating.value = true
  statusError.value = null

  try {
    const { data } = await api.patch(`/applications/${application.value.id}/status`, {
      status: selectedStatus.value,
    })
    application.value = { ...application.value, ...data }
  } catch (err: any) {
    statusError.value = err.response?.data?.error?.message ?? 'Gagal mengubah status.'
    // Reset dropdown
    selectedStatus.value = application.value!.status
  } finally {
    statusUpdating.value = false
  }
}

function formatDate(iso: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

</script>

<template>
  <div class="detail-page">
    <button class="back-link" @click="router.back()">
      <ArrowLeft :size="18" />
      <span>Kembali</span>
    </button>

    <div v-if="loading" class="state-message">Memuat data pelamar...</div>

    <template v-else-if="application">
      <!-- Header -->
      <div class="detail-header">
        <div class="detail-header__main">
          <h1 class="detail-header__name">
            {{ application.additional_data?.name ?? application.applicant?.name ?? 'Pelamar' }}
          </h1>
          <div class="detail-header__meta">
            <span>{{ application.applicant?.email }}</span>
            <span class="dot">&middot;</span>
            <span>Dilamar {{ formatDate(application.applied_at) }}</span>
          </div>
        </div>
        <StatusBadge :status="application.status" />
      </div>

      <div class="detail-grid">
        <!-- Left column: Applicant data -->
        <div class="detail-card">
          <h2 class="detail-card__title">Data Pelamar</h2>

          <div class="info-list">
            <div class="info-item">
              <Mail :size="16" class="info-item__icon" />
              <div>
                <span class="info-item__label">Email</span>
                <span class="info-item__value">{{ application.applicant?.email ?? '-' }}</span>
              </div>
            </div>
            <div class="info-item">
              <Phone :size="16" class="info-item__icon" />
              <div>
                <span class="info-item__label">Nomor HP</span>
                <span class="info-item__value">{{ application.additional_data?.phone ?? '-' }}</span>
              </div>
            </div>
            <div class="info-item">
              <MapPin :size="16" class="info-item__icon" />
              <div>
                <span class="info-item__label">Alamat</span>
                <span class="info-item__value">{{ application.additional_data?.address ?? '-' }}</span>
              </div>
            </div>
          </div>

          <!-- CV download -->
          <div class="cv-section">
            <h3 class="cv-section__title">CV Pelamar</h3>
            <div class="cv-card">
              <FileText :size="24" class="cv-card__icon" />
              <div class="cv-card__info">
                <span class="cv-card__name">
                  {{ application.cv_original_filename ?? 'cv.pdf' }}
                </span>
              </div>
              <button class="btn btn--outline" :disabled="cvLoading" @click="downloadCv">
                <Loader2 v-if="cvLoading" :size="16" class="spin" />
                <Download v-else :size="16" />
                <span>{{ cvLoading ? 'Mengunduh...' : 'Unduh CV' }}</span>
              </button>
            </div>
            <div v-if="cvError" class="cv-error">
              <AlertCircle :size="16" />
              <span>{{ cvError }}</span>
            </div>
          </div>
        </div>

        <!-- Right column: Status -->
        <div class="detail-card">
          <h2 class="detail-card__title">Status Lamaran</h2>

          <div class="status-section">
            <label for="status-select" class="status-label">Ubah Status</label>
            <div class="status-controls">
              <select
                id="status-select"
                v-model="selectedStatus"
                class="status-select"
                :disabled="statusUpdating"
              >
                <option
                  v-for="opt in statusOptions"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
              <button
                class="btn btn--primary btn--sm"
                :disabled="statusUpdating || selectedStatus === application.status"
                @click="updateStatus"
              >
                <Loader2 v-if="statusUpdating" :size="14" class="spin" />
                <span>{{ statusUpdating ? 'Menyimpan...' : 'Simpan' }}</span>
              </button>
            </div>
            <p v-if="statusError" class="status-error">{{ statusError }}</p>
          </div>

          <!-- Status history -->
          <div v-if="application.status_history?.length" class="history-section">
            <h3 class="history-title">Riwayat Perubahan Status</h3>
            <div class="history-list">
              <div
                v-for="entry in application.status_history"
                :key="entry.id"
                class="history-item"
              >
                <div class="history-item__dot"></div>
                <div class="history-item__content">
                  <span class="history-item__change">
                    <StatusBadge :status="entry.previous_status ?? 'pending'" />
                    <span class="arrow">&rarr;</span>
                    <StatusBadge :status="entry.new_status" />
                  </span>
                  <span class="history-item__date">{{ formatDate(entry.changed_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interview scheduling section -->
      <InterviewScheduler
        :application-id="application.id"
        :application-status="application.status"
        :applicant-name="application.additional_data?.name ?? application.applicant?.name ?? 'Pelamar'"
      />

      <!-- Per-application chat thread (FR-017) -->
      <ChatThread
        :application-id="application.id"
        :counterpart-name="application.additional_data?.name ?? application.applicant?.name ?? 'Pelamar'"
      />
    </template>
  </div>
</template>

<style scoped>
.detail-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 24px 16px 64px;
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

.state-message {
  text-align: center;
  padding: 48px 0;
  color: var(--color-text-secondary);
}

.detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
}

.detail-header__main {
  min-width: 0;
}

.detail-header__name {
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 4px;
}

.detail-header__meta {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 768px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}

.detail-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 24px;
}

.detail-card__title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
}

.info-item {
  display: flex;
  gap: 12px;
}

.info-item__icon {
  flex-shrink: 0;
  color: var(--color-text-secondary);
  margin-top: 2px;
}

.info-item__label {
  display: block;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin-bottom: 2px;
}

.info-item__value {
  font-size: 0.875rem;
  color: var(--color-text-primary);
  word-break: break-word;
}

/* CV */
.cv-section__title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 10px;
}

.cv-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: var(--color-background);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.cv-card__icon {
  color: var(--color-primary);
  flex-shrink: 0;
}

.cv-card__info {
  flex: 1;
  min-width: 0;
}

.cv-card__name {
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-primary);
  word-break: break-all;
}

.cv-error {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding: 10px 12px;
  background: #fef3c7;
  border-radius: 8px;
  font-size: 0.8125rem;
  color: #92400e;
}

/* Status */
.status-label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--color-text-primary);
  margin-bottom: 8px;
}

.status-controls {
  display: flex;
  gap: 8px;
}

.status-select {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--color-text-primary);
  background: var(--color-background);
  font-family: inherit;
}

.status-select:focus {
  outline: none;
  border-color: var(--color-primary);
}

.status-error {
  margin: 8px 0 0;
  font-size: 0.8125rem;
  color: var(--color-error);
}

/* History */
.history-section {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.history-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 14px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-item {
  display: flex;
  gap: 10px;
}

.history-item__dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-primary);
  margin-top: 6px;
  flex-shrink: 0;
}

.history-item__content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.history-item__change {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.8125rem;
}

.arrow {
  color: var(--color-text-secondary);
}

.history-item__date {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  border: none;
  transition: background-color 0.15s;
  font-family: inherit;
  white-space: nowrap;
}

.btn--primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn--primary:hover {
  background: var(--color-primary-hover);
}

.btn--primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--outline {
  background: transparent;
  border: 1px solid var(--color-border);
  color: var(--color-text-primary);
}

.btn--outline:hover {
  background: var(--color-surface);
  border-color: var(--color-primary);
}

.btn--outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--sm {
  padding: 8px 16px;
  font-size: 0.8125rem;
}

.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
