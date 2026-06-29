<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../composables/useApi'
import StatusBadge from '../../components/StatusBadge.vue'
import { ArrowLeft, Users, Search } from 'lucide-vue-next'
import type { Application, JobPosting } from '../../types'

const route = useRoute()
const router = useRouter()
const job = ref<JobPosting | null>(null)
const applications = ref<Application[]>([])
const loading = ref(true)
const searchQuery = ref('')

onMounted(async () => {
  try {
    const [{ data: jobData }, { data: appsData }] = await Promise.all([
      api.get(`/jobs/${route.params.id}`),
      api.get(`/jobs/${route.params.id}/applications`),
    ])
    job.value = jobData
    applications.value = appsData.data
  } catch {
    router.replace({ name: 'not-found' })
  } finally {
    loading.value = false
  }
})

const filteredApplications = computed(() => {
  if (!searchQuery.value.trim()) return applications.value
  const q = searchQuery.value.toLowerCase()
  return applications.value.filter(
    (a) =>
      a.applicant?.name.toLowerCase().includes(q) ||
      a.applicant?.email.toLowerCase().includes(q),
  )
})

function formatDate(iso: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <div class="applicant-list-page">
    <button class="back-link" @click="router.push({ name: 'hr-jobs' })">
      <ArrowLeft :size="18" />
      <span>Kembali ke daftar lowongan</span>
    </button>

    <div v-if="loading" class="state-message">Memuat data pelamar...</div>

    <template v-else-if="job">
      <div class="page-header">
        <h1 class="page-title">Pelamar — {{ job.title }}</h1>
        <p class="page-subtitle">
          {{ job.location }} &middot; {{ applications.length }} pelamar
        </p>
      </div>

      <!-- Empty -->
      <div v-if="applications.length === 0" class="empty-state">
        <Users :size="48" class="empty-state__icon" />
        <h2 class="empty-state__title">Belum ada pelamar</h2>
        <p class="empty-state__text">Belum ada yang melamar lowongan ini.</p>
      </div>

      <template v-else>
        <!-- Search -->
        <div class="search-bar">
          <Search :size="18" class="search-bar__icon" />
          <input
            v-model="searchQuery"
            type="text"
            class="search-bar__input"
            placeholder="Cari pelamar berdasarkan nama atau email..."
          />
        </div>

        <!-- Applicant table -->
        <div class="table-wrapper">
          <table class="app-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Tanggal Melamar</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredApplications.length === 0">
                <td colspan="5" class="no-results">
                  Tidak ada pelamar yang cocok dengan pencarian.
                </td>
              </tr>
              <tr
                v-for="app in filteredApplications"
                :key="app.id"
                class="app-row"
                @click="router.push({ name: 'hr-applicant-detail', params: { id: app.id } })"
              >
                <td class="app-row__name">{{ app.applicant?.name ?? '-' }}</td>
                <td class="app-row__email">{{ app.applicant?.email ?? '-' }}</td>
                <td>{{ formatDate(app.applied_at) }}</td>
                <td><StatusBadge :status="app.status" /></td>
                <td class="app-row__action">Detail &rarr;</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </template>
  </div>
</template>

<style scoped>
.applicant-list-page {
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

.page-header {
  margin-bottom: 20px;
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
  margin: 0;
}

.search-bar {
  position: relative;
  margin-bottom: 16px;
}

.search-bar__icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-secondary);
}

.search-bar__input {
  width: 100%;
  padding: 10px 12px 10px 40px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.875rem;
  background: var(--color-background);
  color: var(--color-text-primary);
  font-family: inherit;
  box-sizing: border-box;
}

.search-bar__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-subtle);
}

.table-wrapper {
  border: 1px solid var(--color-border);
  border-radius: 10px;
  overflow: hidden;
}

.app-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.app-table thead {
  background: var(--color-surface);
}

.app-table th {
  text-align: left;
  padding: 12px 16px;
  font-weight: 600;
  color: var(--color-text-secondary);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.app-table td {
  padding: 14px 16px;
  border-top: 1px solid var(--color-border);
  color: var(--color-text-primary);
}

.app-row {
  cursor: pointer;
  transition: background-color 0.1s;
}

.app-row:hover {
  background: var(--color-primary-subtle);
}

.app-row__name {
  font-weight: 500;
}

.app-row__email {
  color: var(--color-text-secondary);
  font-size: 0.8125rem;
}

.app-row__action {
  color: var(--color-primary);
  font-weight: 500;
  text-align: right;
}

.no-results {
  text-align: center;
  padding: 32px 16px;
  color: var(--color-text-secondary);
  font-style: italic;
}
</style>
