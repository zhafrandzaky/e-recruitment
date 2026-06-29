<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Calendar, Video, AlertCircle, Loader2, ExternalLink, XCircle, RefreshCw, Link2 } from 'lucide-vue-next'
import { useInterviews } from '../composables/useInterviews'
import type { Interview, ApplicationStatus } from '../types'

const props = defineProps<{
  applicationId: string
  applicationStatus: ApplicationStatus
  applicantName: string
}>()

const emit = defineEmits<{
  (e: 'interview-changed'): void
}>()

const {
  loading,
  error,
  fetchInterview,
  scheduleInterview,
  rescheduleInterview,
  cancelInterview,
} = useInterviews()

const interview = ref<Interview | null>(null)
const scheduledDate = ref('')
const scheduledTime = ref('')
const meetingLink = ref('')
const submitting = ref(false)
const localError = ref<string | null>(null)
const confirmCancel = ref(false)

onMounted(async () => {
  interview.value = await fetchInterview(props.applicationId)
})

function getScheduledAt(): string {
  return `${scheduledDate.value}T${scheduledTime.value}:00`
}

async function handleSchedule() {
  if (!scheduledDate.value || !scheduledTime.value) {
    localError.value = 'Pilih tanggal dan jam interview.'
    return
  }
  if (!meetingLink.value) {
    localError.value = 'Masukkan link meeting (Google Meet, Zoom, atau platform lainnya).'
    return
  }

  submitting.value = true
  localError.value = null

  const result = await scheduleInterview(props.applicationId, getScheduledAt(), meetingLink.value)
  if (result) {
    interview.value = result
    meetingLink.value = ''
    scheduledDate.value = ''
    scheduledTime.value = ''
    emit('interview-changed')
  } else {
    localError.value = error.value
  }

  submitting.value = false
}

async function handleReschedule() {
  if (!scheduledDate.value || !scheduledTime.value) {
    localError.value = 'Pilih tanggal dan jam interview baru.'
    return
  }

  submitting.value = true
  localError.value = null

  const result = await rescheduleInterview(props.applicationId, getScheduledAt())
  if (result) {
    interview.value = result
    emit('interview-changed')
  } else {
    localError.value = error.value
  }

  submitting.value = false
}

async function handleCancel() {
  submitting.value = true
  localError.value = null

  const success = await cancelInterview(props.applicationId)
  if (success) {
    interview.value = null
    confirmCancel.value = false
    emit('interview-changed')
  } else {
    localError.value = error.value
  }

  submitting.value = false
}

function formatScheduledAt(iso: string): string {
  return new Date(iso).toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    timeZoneName: 'short',
  })
}

// Get today's date in YYYY-MM-DD for min attribute
const todayStr = new Date().toISOString().slice(0, 10)
</script>

<template>
  <div class="interview-card">
    <h2 class="interview-card__title">
      <Video :size="18" />
      <span>Penjadwalan Interview</span>
    </h2>

    <div v-if="loading" class="interview-loading">
      <Loader2 :size="16" class="spin" />
      <span>Memuat data interview...</span>
    </div>

    <!-- Existing scheduled interview -->
    <template v-else-if="interview && interview.status === 'scheduled'">
      <div class="interview-detail">
        <div class="interview-detail__row">
          <Calendar :size="16" class="interview-detail__icon" />
          <span>{{ formatScheduledAt(interview.scheduled_at) }}</span>
        </div>
        <div class="interview-detail__row">
          <Video :size="16" class="interview-detail__icon" />
          <a :href="interview.meeting_link" target="_blank" rel="noopener noreferrer" class="interview-link">
            {{ interview.meeting_link }}
            <ExternalLink :size="12" />
          </a>
        </div>
      </div>

      <p class="interview-note">
        Interview dilaksanakan melalui platform meeting eksternal pada link di atas.
        Sistem ini hanya menangani penjadwalan — bukan video call yang tertanam di halaman ini.
      </p>

      <!-- Reschedule form -->
      <div class="interview-form">
        <h3 class="interview-form__title">
          <RefreshCw :size="14" />
          Ubah Jadwal
        </h3>
        <div class="datetime-row">
          <input
            type="date"
            v-model="scheduledDate"
            :min="todayStr"
            class="input input--date"
          />
          <input
            type="time"
            v-model="scheduledTime"
            class="input input--time"
          />
        </div>
        <div class="action-row">
          <button
            class="btn btn--primary btn--sm"
            :disabled="submitting || !scheduledDate || !scheduledTime"
            @click="handleReschedule"
          >
            <Loader2 v-if="submitting" :size="14" class="spin" />
            <span>Simpan Perubahan</span>
          </button>
          <button
            v-if="!confirmCancel"
            class="btn btn--danger-outline btn--sm"
            :disabled="submitting"
            @click="confirmCancel = true"
          >
            <XCircle :size="14" />
            <span>Batalkan Interview</span>
          </button>
          <template v-else>
            <span class="confirm-text">Yakin batalkan?</span>
            <button
              class="btn btn--danger btn--sm"
              :disabled="submitting"
              @click="handleCancel"
            >
              <Loader2 v-if="submitting" :size="14" class="spin" />
              <span>Ya, Batalkan</span>
            </button>
            <button
              class="btn btn--ghost btn--sm"
              :disabled="submitting"
              @click="confirmCancel = false"
            >
              Batal
            </button>
          </template>
        </div>
      </div>

      <p v-if="localError" class="interview-error">
        <AlertCircle :size="14" />
        {{ localError }}
      </p>
    </template>

    <!-- No interview yet — show scheduling form -->
    <template v-else>
      <div v-if="props.applicationStatus !== 'shortlisted'" class="interview-unavailable">
        <AlertCircle :size="16" />
        <span>Interview hanya dapat dijadwalkan setelah pelamar berstatus "Lolos Seleksi Berkas".</span>
      </div>

      <div v-else class="interview-form">
        <div class="datetime-row">
          <input
            type="date"
            v-model="scheduledDate"
            :min="todayStr"
            class="input input--date"
            placeholder="Pilih tanggal"
          />
          <input
            type="time"
            v-model="scheduledTime"
            class="input input--time"
            placeholder="Pilih jam"
          />
        </div>

        <div class="link-row">
          <Link2 :size="16" class="link-row__icon" />
          <input
            type="url"
            v-model="meetingLink"
            class="input input--link"
            placeholder="Link meeting (Google Meet, Zoom, dll)"
          />
        </div>

        <button
          class="btn btn--primary"
          :disabled="submitting || !scheduledDate || !scheduledTime || !meetingLink"
          @click="handleSchedule"
        >
          <Loader2 v-if="submitting" :size="16" class="spin" />
          <Calendar v-else :size="16" />
          <span>{{ submitting ? 'Menjadwalkan...' : 'Buat Jadwal Interview' }}</span>
        </button>

        <p class="interview-note">
          Link meeting diisi manual oleh HR — bisa Google Meet, Zoom, atau platform lain.
          Interview berlangsung di platform meeting eksternal — bukan di halaman ini.
        </p>

        <p v-if="localError" class="interview-error">
          <AlertCircle :size="14" />
          {{ localError }}
        </p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.interview-card {
  margin-top: 20px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 24px;
}

.interview-card__title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 8px;
}

.interview-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
}

.interview-detail {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.interview-detail__row {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.875rem;
  color: var(--color-text-primary);
}

.interview-detail__icon {
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.interview-link {
  color: var(--color-primary);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  word-break: break-all;
}

.interview-link:hover {
  text-decoration: underline;
}

.interview-note {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 12px 0;
  line-height: 1.5;
  padding: 10px 12px;
  background: var(--color-background);
  border-radius: 8px;
  border-left: 3px solid var(--color-primary);
}

.interview-unavailable {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  padding: 14px;
  background: var(--color-background);
  border-radius: 8px;
}

.interview-form {
  margin-top: 16px;
}

.interview-form__title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.datetime-row {
  display: flex;
  gap: 10px;
  margin-bottom: 12px;
}

.link-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.link-row__icon {
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.input--link {
  flex: 1;
}

.input {
  padding: 10px 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-background);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  font-family: inherit;
}

.input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-subtle);
}

.input--date {
  flex: 1;
}

.input--time {
  width: 140px;
}

.action-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.confirm-text {
  font-size: 0.8125rem;
  color: var(--color-error);
  font-weight: 500;
}

.interview-error {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  font-size: 0.8125rem;
  color: var(--color-error);
  margin-top: 12px;
  padding: 10px 12px;
  background: rgba(192, 52, 52, 0.06);
  border-radius: 8px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  border: none;
  transition: background 0.15s, opacity 0.15s;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn--primary {
  background: var(--color-primary);
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background: var(--color-primary-hover);
}

.btn--danger {
  background: var(--color-error);
  color: #fff;
}

.btn--danger:hover:not(:disabled) {
  opacity: 0.9;
}

.btn--danger-outline {
  border: 1px solid var(--color-error);
  color: var(--color-error);
  background: transparent;
}

.btn--danger-outline:hover:not(:disabled) {
  background: rgba(192, 52, 52, 0.06);
}

.btn--ghost {
  background: transparent;
  color: var(--color-text-secondary);
  border: 1px solid var(--color-border);
}

.btn--ghost:hover:not(:disabled) {
  background: var(--color-background);
}

.btn--sm {
  padding: 6px 14px;
  font-size: 0.8125rem;
}

.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
