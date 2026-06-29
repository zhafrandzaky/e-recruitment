<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../composables/useApi'
import { useInterviews } from '../../composables/useInterviews'
import StatusBadge from '../../components/StatusBadge.vue'
import ChatThread from '../../components/ChatThread.vue'
import { ArrowLeft, Briefcase, CalendarClock, Video } from 'lucide-vue-next'
import type { Application, Interview } from '../../types'

const route = useRoute()
const router = useRouter()
const { fetchInterview } = useInterviews()

const application = ref<Application | null>(null)
const interview = ref<Interview | null>(null)
const loading = ref(true)

onMounted(async () => {
  const id = String(route.params.id)
  try {
    const { data } = await api.get(`/applications/${id}`)
    application.value = data
    interview.value = await fetchInterview(id)
  } catch {
    router.replace({ name: 'not-found' })
  } finally {
    loading.value = false
  }
})

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
    <button class="back-link" @click="router.push({ name: 'my-applications' })">
      <ArrowLeft :size="18" />
      <span>Lamaran Saya</span>
    </button>

    <div v-if="loading" class="state-message">Memuat detail lamaran...</div>

    <template v-else-if="application">
      <div class="detail-header">
        <div class="detail-header__main">
          <h1 class="detail-header__title">{{ application.job?.title ?? 'Lowongan' }}</h1>
          <div class="detail-header__meta">
            <span class="meta-item"><Briefcase :size="14" /> {{ application.job?.location ?? '-' }}</span>
            <span class="meta-item">Dilamar {{ formatDate(application.applied_at) }}</span>
          </div>
        </div>
        <StatusBadge :status="application.status" />
      </div>

      <div class="detail-card">
        <h2 class="detail-card__title">Status Lamaran</h2>
        <div class="status-row">
          <StatusBadge :status="application.status" />
        </div>

        <div v-if="application.status_history?.length" class="history-section">
          <h3 class="history-title">Riwayat Perubahan</h3>
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

      <!-- Interview, if scheduled -->
      <div v-if="interview" class="detail-card">
        <h2 class="detail-card__title">Jadwal Interview</h2>
        <div class="interview-row">
          <CalendarClock :size="18" class="interview-icon" />
          <span>{{ formatDate(interview.scheduled_at) }}</span>
        </div>
        <a class="interview-link" :href="interview.meeting_link" target="_blank" rel="noopener noreferrer">
          <Video :size="16" />
          <span>Buka Tautan Meeting</span>
        </a>
      </div>

      <!-- Per-application chat thread (FR-017) -->
      <ChatThread :application-id="application.id" counterpart-name="Tim Rekrutmen" />
    </template>
  </div>
</template>

<style scoped>
.detail-page {
  max-width: 720px;
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

.detail-header__title {
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 6px;
}

.detail-header__meta {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.detail-card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 16px;
}

.detail-card__title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 16px;
}

.status-row {
  margin-bottom: 4px;
}

.history-section {
  margin-top: 20px;
  padding-top: 16px;
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

.interview-row {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
  color: var(--color-text-primary);
  margin-bottom: 12px;
}

.interview-icon {
  color: var(--color-primary);
}

.interview-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-primary);
  text-decoration: none;
}

.interview-link:hover {
  text-decoration: underline;
}
</style>
