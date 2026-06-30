<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, Pencil, Trash2, ToggleLeft, ToggleRight, Briefcase, Users } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import { useJobs } from '../../composables/useJobs'
import type { JobPosting } from '../../types'

const router = useRouter()
const { isLoading, updateJobStatus, deleteJob } = useJobs()

const jobs = ref<JobPosting[]>([])
const meta = ref({ page: 1, per_page: 15, total: 0 })
const isActionLoading = ref<string | null>(null)

import api from '../../composables/useApi'
import type { PaginatedResponse } from '../../types'

async function loadJobs(page = 1) {
  isLoading.value = true
  try {
    const { data } = await api.get<PaginatedResponse<JobPosting>>('/jobs', {
      params: { page, per_page: 20 },
    })
    jobs.value = data.data
    meta.value = data.meta
  } finally {
    isLoading.value = false
  }
}

async function toggleStatus(job: JobPosting) {
  isActionLoading.value = job.id
  try {
    const newStatus = job.status === 'active' ? 'closed' : 'active'
    await updateJobStatus(job.id, newStatus)
    job.status = newStatus
  } finally {
    isActionLoading.value = null
  }
}

async function handleDelete(job: JobPosting) {
  if (!confirm(`Hapus lowongan "${job.title}"? Tindakan ini tidak dapat dibatalkan.`)) return
  isActionLoading.value = job.id
  try {
    await deleteJob(job.id)
    jobs.value = jobs.value.filter((j) => j.id !== job.id)
  } finally {
    isActionLoading.value = null
  }
}

function statusBadge(status: string): { label: string; style: string } {
  const map: Record<string, { label: string; style: string }> = {
    active: { label: 'Aktif', style: 'background: var(--color-accent-subtle); color: var(--color-accent)' },
    draft: { label: 'Draft', style: 'background: var(--color-surface); color: var(--color-text-secondary); border: 1px solid var(--color-border)' },
    closed: { label: 'Ditutup', style: 'background: color-mix(in srgb, var(--color-error) 10%, transparent); color: var(--color-error)' },
  }
  return map[status] ?? map.draft
}

onMounted(() => loadJobs())
</script>

<template>
  <AppLayout>
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Kelola Lowongan</h1>
        <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">
          {{ meta.total }} lowongan terdaftar
        </p>
      </div>
      <button
        @click="router.push('/hr/jobs/create')"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
        style="background: var(--color-primary); color: #ffffff"
      >
        <Plus :size="16" />
        Buat Lowongan
      </button>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex flex-col gap-3">
      <div v-for="i in 4" :key="i" class="rounded-xl border p-5 animate-pulse" style="background: var(--color-surface); border-color: var(--color-border)">
        <div class="h-5 w-1/3 rounded mb-3" style="background: var(--color-border)" />
        <div class="h-3.5 w-1/5 rounded" style="background: var(--color-border)" />
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="!jobs.length" class="rounded-xl border p-12 text-center" style="background: var(--color-surface); border-color: var(--color-border)">
      <Briefcase :size="48" class="mx-auto mb-4" style="color: var(--color-text-secondary)" />
      <p class="font-medium" style="color: var(--color-text-primary)">Belum ada lowongan</p>
      <button @click="router.push('/hr/jobs/create')" class="mt-3 text-sm font-medium transition-colors" style="color: var(--color-primary)">
        Buat lowongan pertama
      </button>
    </div>

    <!-- Table view -->
    <div v-else class="rounded-xl border overflow-hidden" style="border-color: var(--color-border)">
      <table class="w-full text-sm">
        <thead>
          <tr style="background: var(--color-surface); border-bottom: 1px solid var(--color-border)">
            <th class="text-left px-5 py-3 font-semibold" style="color: var(--color-text-primary)">Judul</th>
            <th class="text-left px-4 py-3 font-semibold hidden md:table-cell" style="color: var(--color-text-primary)">Lokasi</th>
            <th class="text-left px-4 py-3 font-semibold hidden sm:table-cell" style="color: var(--color-text-primary)">Deadline</th>
            <th class="text-left px-4 py-3 font-semibold" style="color: var(--color-text-primary)">Status</th>
            <th class="px-4 py-3" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="job in jobs"
            :key="job.id"
            class="border-t transition-colors"
            style="border-color: var(--color-border); background: var(--color-background)"
          >
            <td class="px-5 py-4 font-medium" style="color: var(--color-text-primary)">{{ job.title }}</td>
            <td class="px-4 py-4 hidden md:table-cell" style="color: var(--color-text-secondary)">{{ job.location ?? '-' }}</td>
            <td class="px-4 py-4 hidden sm:table-cell" style="color: var(--color-text-secondary)">
              {{ job.deadline ? new Date(job.deadline).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
            </td>
            <td class="px-4 py-4">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium" :style="statusBadge(job.status).style">
                {{ statusBadge(job.status).label }}
              </span>
            </td>
            <td class="px-4 py-4">
              <div class="flex items-center justify-end gap-1">
                <button
                  @click="router.push(`/hr/jobs/${job.id}/applicants`)"
                  class="p-1.5 rounded-md transition-colors hover:text-[var(--color-primary)]"
                  style="color: var(--color-text-secondary)"
                  title="Lihat pelamar"
                >
                  <Users :size="16" />
                </button>
                <button
                  @click="toggleStatus(job)"
                  :disabled="isActionLoading === job.id"
                  class="p-1.5 rounded-md transition-colors"
                  style="color: var(--color-text-secondary)"
                  :title="job.status === 'active' ? 'Tutup lowongan' : 'Aktifkan lowongan'"
                >
                  <ToggleRight v-if="job.status === 'active'" :size="18" style="color: var(--color-accent)" />
                  <ToggleLeft v-else :size="18" />
                </button>
                <button
                  @click="router.push(`/hr/jobs/${job.id}/edit`)"
                  class="p-1.5 rounded-md transition-colors"
                  style="color: var(--color-text-secondary)"
                  title="Edit lowongan"
                >
                  <Pencil :size="16" />
                </button>
                <button
                  @click="handleDelete(job)"
                  :disabled="isActionLoading === job.id"
                  class="p-1.5 rounded-md transition-colors"
                  style="color: var(--color-text-secondary)"
                  title="Hapus lowongan"
                >
                  <Trash2 :size="16" style="color: var(--color-error)" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta.total > meta.per_page" class="flex justify-between items-center mt-6 pt-4 border-t" style="border-color: var(--color-border)">
      <p class="text-sm" style="color: var(--color-text-secondary)">{{ meta.total }} total</p>
      <div class="flex gap-2">
        <button :disabled="meta.page <= 1" @click="loadJobs(meta.page - 1)" class="px-3 py-1.5 rounded-md text-sm border transition-colors" style="border-color: var(--color-border); color: var(--color-text-secondary)">Sebelumnya</button>
        <button :disabled="meta.page * meta.per_page >= meta.total" @click="loadJobs(meta.page + 1)" class="px-3 py-1.5 rounded-md text-sm border transition-colors" style="border-color: var(--color-border); color: var(--color-text-secondary)">Selanjutnya</button>
      </div>
    </div>
  </AppLayout>
</template>
