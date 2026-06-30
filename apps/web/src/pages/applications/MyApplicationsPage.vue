<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import api from '../../composables/useApi'
import StatusBadge from '../../components/StatusBadge.vue'
import AppLayout from '../../layouts/AppLayout.vue'
import { FileText, Briefcase, ChevronRight } from 'lucide-vue-next'
import type { Application } from '../../types'

const router = useRouter()
const auth = useAuthStore()
const applications = ref<Application[]>([])
const loading = ref(true)

onMounted(async () => {
  if (!auth.isAuthenticated) {
    router.replace({ name: 'login', query: { redirect: '/applications/me' } })
    return
  }

  try {
    const { data } = await api.get('/applications/me')
    applications.value = data.data
  } catch {
    // silently fail — user sees empty state
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
  <AppLayout>
  <div class="my-apps-page">
    <div class="page-header">
      <h1 class="page-title">Lamaran Saya</h1>
      <p class="page-subtitle">Pantau status semua lamaran yang telah Anda kirimkan.</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="state-message">Memuat data lamaran...</div>

    <!-- Empty -->
    <div v-else-if="applications.length === 0" class="empty-state">
      <Briefcase :size="48" class="empty-state__icon" />
      <h2 class="empty-state__title">Belum ada lamaran</h2>
      <p class="empty-state__text">
        Anda belum mengirimkan lamaran apapun.
        Jelajahi lowongan yang tersedia dan kirimkan lamaran pertama Anda.
      </p>
      <button class="btn btn--primary" @click="router.push({ name: 'jobs' })">
        Lihat Lowongan
      </button>
    </div>

    <!-- Application list -->
    <div v-else class="app-list">
      <div
        v-for="app in applications"
        :key="app.id"
        class="app-card"
        @click="router.push({ name: 'application-detail', params: { id: app.id } })"
      >
        <div class="app-card__main">
          <div class="app-card__icon">
            <FileText :size="20" />
          </div>
          <div class="app-card__info">
            <h3 class="app-card__job-title">{{ app.job?.title ?? 'Lowongan' }}</h3>
            <p class="app-card__meta">
              {{ app.job?.location ?? '-' }} &middot;
              Dilamar {{ formatDate(app.applied_at) }}
            </p>
          </div>
        </div>
        <div class="app-card__right">
          <StatusBadge :status="app.status" />
          <ChevronRight :size="18" class="app-card__chevron" />
        </div>
      </div>
    </div>
  </div>
  </AppLayout>
</template>

<style scoped>
.my-apps-page {
  max-width: 720px;
  margin: 0 auto;
  padding: 24px 16px 64px;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 4px;
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.state-message {
  text-align: center;
  padding: 48px 0;
  color: var(--color-text-secondary);
}

.empty-state {
  text-align: center;
  padding: 64px 24px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
}

.empty-state__icon {
  color: var(--color-text-secondary);
  margin-bottom: 16px;
}

.empty-state__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 8px;
}

.empty-state__text {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0 0 20px;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
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

.app-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.app-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.app-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.app-card__main {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}

.app-card__icon {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: var(--color-primary-subtle);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-card__info {
  min-width: 0;
}

.app-card__job-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-card__meta {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.app-card__right {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.app-card__chevron {
  color: var(--color-text-secondary);
}
</style>
