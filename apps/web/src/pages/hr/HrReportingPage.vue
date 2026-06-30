<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { BarChart3, Clock, Users, Filter, AlertCircle } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import { useReports } from '../../composables/useReports'
import BarList from '../../components/charts/BarList.vue'
import FunnelChart from '../../components/charts/FunnelChart.vue'
import StatFigure from '../../components/charts/StatFigure.vue'
import type { JobFunnel } from '../../types'

const { overview, isLoading, error, fetchOverview, fetchJobFunnel } = useReports()

const totalApplicants = computed(() =>
  overview.value
    ? Object.values(overview.value.funnel).reduce((sum, n) => sum + n, 0)
    : 0,
)

const barItems = computed(() =>
  (overview.value?.applicants_per_job ?? []).map((j) => ({
    id: j.job_id,
    label: j.job_title,
    value: j.count,
  })),
)

// Per-job funnel drill-down (exercises GET /reports/jobs/{id}/funnel).
const selectedJobId = ref('')
const jobFunnel = ref<JobFunnel | null>(null)
const jobFunnelLoading = ref(false)
const jobFunnelError = ref<string | null>(null)

async function loadJobFunnel(): Promise<void> {
  jobFunnel.value = null
  jobFunnelError.value = null
  if (!selectedJobId.value) return

  jobFunnelLoading.value = true
  try {
    jobFunnel.value = await fetchJobFunnel(selectedJobId.value)
  } catch {
    jobFunnelError.value = 'Gagal memuat funnel lowongan ini.'
  } finally {
    jobFunnelLoading.value = false
  }
}

onMounted(fetchOverview)
</script>

<template>
  <AppLayout>
  <div class="reporting-page">
    <header class="page-header">
      <h1 class="page-title">
        <BarChart3 :size="22" />
        Laporan Rekrutmen
      </h1>
      <p class="page-subtitle">
        Ringkasan pipeline rekrutmen: jumlah pelamar per lowongan, funnel seleksi, dan rata-rata waktu perekrutan.
      </p>
    </header>

    <div v-if="isLoading" class="state-message">Memuat data laporan...</div>

    <div v-else-if="error" class="state-error">
      <AlertCircle :size="18" />
      <span>{{ error }}</span>
    </div>

    <Transition v-else name="fade" appear>
      <div v-if="overview" class="report-content">
        <!-- Summary stats -->
        <div class="stat-grid">
          <div class="card stat-card">
            <StatFigure
              label="Total Pelamar"
              :value="totalApplicants"
              unit="pelamar"
              :icon="Users"
            />
          </div>
          <div class="card stat-card">
            <StatFigure
              label="Rata-rata Waktu Perekrutan"
              :value="overview.avg_time_to_hire_days"
              unit="hari"
              :icon="Clock"
              empty-hint="Belum ada pelamar yang diterima"
            />
          </div>
        </div>

        <!-- Selection funnel (overall) -->
        <section class="card">
          <h2 class="card-title">Funnel Seleksi</h2>
          <p class="card-hint">Distribusi seluruh lamaran berdasarkan status terkini.</p>
          <FunnelChart :funnel="overview.funnel" />
        </section>

        <!-- Applicants per job -->
        <section class="card">
          <h2 class="card-title">Pelamar per Lowongan</h2>
          <p class="card-hint">Jumlah lamaran yang diterima untuk setiap lowongan.</p>
          <BarList :items="barItems" empty-text="Belum ada lowongan." />
        </section>

        <!-- Per-job funnel drill-down -->
        <section class="card">
          <h2 class="card-title">Funnel per Lowongan</h2>
          <p class="card-hint">Pilih satu lowongan untuk melihat funnel seleksinya.</p>

          <div class="job-picker">
            <Filter :size="16" class="job-picker__icon" />
            <select
              v-model="selectedJobId"
              class="job-picker__select"
              aria-label="Pilih lowongan untuk melihat funnel"
              @change="loadJobFunnel"
            >
              <option value="">— Pilih lowongan —</option>
              <option v-for="j in overview.applicants_per_job" :key="j.job_id" :value="j.job_id">
                {{ j.job_title }} ({{ j.count }})
              </option>
            </select>
          </div>

          <div v-if="jobFunnelLoading" class="state-message">Memuat funnel...</div>
          <div v-else-if="jobFunnelError" class="state-error">
            <AlertCircle :size="18" />
            <span>{{ jobFunnelError }}</span>
          </div>
          <div v-else-if="jobFunnel" class="job-funnel">
            <h3 class="job-funnel__title">{{ jobFunnel.job_title }}</h3>
            <FunnelChart :funnel="jobFunnel.funnel" />
          </div>
        </section>
      </div>
    </Transition>
  </div>
  </AppLayout>
</template>

<style scoped>
.reporting-page {
  max-width: 960px;
  margin: 0 auto;
  padding: 24px 16px 64px;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary);
  margin: 0 0 6px;
}

.page-title :deep(svg) {
  color: var(--color-primary);
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0;
  max-width: 56ch;
}

.state-message {
  text-align: center;
  padding: 48px 0;
  color: var(--color-text-secondary);
}

.state-error {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-error) 10%, transparent);
  color: var(--color-error);
  font-size: 0.875rem;
}

.report-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.stat-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 640px) {
  .stat-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 24px;
}

.stat-card {
  display: flex;
}

.card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 4px;
}

.card-hint {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  margin: 0 0 20px;
}

.job-picker {
  position: relative;
  margin-bottom: 20px;
}

.job-picker__icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-secondary);
  pointer-events: none;
}

.job-picker__select {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 0.875rem;
  color: var(--color-text-primary);
  background: var(--color-background);
  font-family: inherit;
}

.job-picker__select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-subtle);
}

.job-funnel__title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--color-text-primary);
  margin: 0 0 16px;
}

/* Subtle fade-in only (DESIGN-SYSTEM.md §6.2 — high-frequency dashboard). */
.fade-enter-active {
  transition: opacity 300ms ease, transform 300ms cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

@media (prefers-reduced-motion: reduce) {
  .fade-enter-active {
    transition: none;
  }
}
</style>
