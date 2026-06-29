<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, MapPin, Clock, Briefcase } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import { useJobs } from '../../composables/useJobs'
import { useAuthStore } from '../../stores/auth'
import type { JobPosting } from '../../types'

const route = useRoute()
const router = useRouter()
const { fetchJob } = useJobs()
const auth = useAuthStore()

const job = ref<JobPosting | null>(null)
const isLoading = ref(true)
const errorMessage = ref('')

function formatDate(date: string | null): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

function isDeadlineSoon(deadline: string | null): boolean {
  if (!deadline) return false
  const diff = new Date(deadline).getTime() - Date.now()
  return diff > 0 && diff < 7 * 24 * 60 * 60 * 1000
}

onMounted(async () => {
  try {
    job.value = await fetchJob(route.params.id as string)
  } catch {
    errorMessage.value = 'Lowongan tidak ditemukan atau sudah ditutup.'
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <AppLayout>
    <!-- Back -->
    <button
      @click="router.push('/jobs')"
      class="inline-flex items-center gap-1.5 text-sm mb-6 transition-colors"
      style="color: var(--color-text-secondary)"
    >
      <ArrowLeft :size="16" />
      Semua Lowongan
    </button>

    <!-- Loading -->
    <div v-if="isLoading" class="rounded-xl border p-8 animate-pulse" style="background: var(--color-surface); border-color: var(--color-border)">
      <div class="h-7 rounded w-1/2 mb-4" style="background: var(--color-border)" />
      <div class="h-4 rounded w-1/4 mb-6" style="background: var(--color-border)" />
      <div class="space-y-2">
        <div class="h-3.5 rounded" style="background: var(--color-border)" />
        <div class="h-3.5 rounded w-5/6" style="background: var(--color-border)" />
        <div class="h-3.5 rounded w-4/6" style="background: var(--color-border)" />
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="errorMessage" class="rounded-xl border p-12 text-center" style="border-color: var(--color-border)">
      <Briefcase :size="48" class="mx-auto mb-4" style="color: var(--color-text-secondary)" />
      <p style="color: var(--color-error)">{{ errorMessage }}</p>
      <button @click="router.push('/jobs')" class="mt-3 text-sm underline" style="color: var(--color-primary)">
        Kembali ke daftar lowongan
      </button>
    </div>

    <!-- Detail -->
    <div v-else-if="job" class="grid lg:grid-cols-3 gap-6">
      <!-- Main content -->
      <div class="lg:col-span-2">
        <div class="rounded-xl border p-8" style="background: var(--color-surface); border-color: var(--color-border)">
          <h1 class="text-2xl font-bold mb-4" style="color: var(--color-text-primary)">{{ job.title }}</h1>

          <!-- Meta badges -->
          <div class="flex flex-wrap gap-3 mb-8 pb-6 border-b" style="border-color: var(--color-border)">
            <span v-if="job.location" class="flex items-center gap-1.5 text-sm px-3 py-1 rounded-full" style="background: var(--color-primary-subtle); color: var(--color-primary)">
              <MapPin :size="13" />
              {{ job.location }}
            </span>
            <span
              v-if="job.deadline"
              class="flex items-center gap-1.5 text-sm px-3 py-1 rounded-full"
              :style="isDeadlineSoon(job.deadline) ? 'background: color-mix(in srgb, var(--color-warning) 12%, transparent); color: var(--color-warning)' : 'background: var(--color-surface); color: var(--color-text-secondary); border: 1px solid var(--color-border)'"
            >
              <Clock :size="13" />
              Deadline: {{ formatDate(job.deadline) }}
            </span>
          </div>

          <!-- Description -->
          <div v-if="job.description" class="mb-8">
            <h2 class="text-base font-semibold mb-3" style="color: var(--color-text-primary)">Deskripsi Pekerjaan</h2>
            <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: var(--color-text-secondary)">{{ job.description }}</p>
          </div>

          <!-- Qualifications -->
          <div v-if="job.qualifications">
            <h2 class="text-base font-semibold mb-3" style="color: var(--color-text-primary)">Kualifikasi</h2>
            <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: var(--color-text-secondary)">{{ job.qualifications }}</p>
          </div>
        </div>
      </div>

      <!-- Sidebar / CTA -->
      <div>
        <div class="rounded-xl border p-6 sticky top-20" style="background: var(--color-surface); border-color: var(--color-border)">
          <div class="mb-4 pb-4 border-b" style="border-color: var(--color-border)">
            <p class="text-xs font-medium mb-1" style="color: var(--color-text-secondary)">Deadline</p>
            <p class="text-sm font-semibold" style="color: var(--color-text-primary)">{{ formatDate(job.deadline) }}</p>
          </div>

          <!-- "Lamar Sekarang" button — wired in Phase 2 -->
          <button
            class="w-full py-3 rounded-lg text-sm font-semibold transition-all duration-200"
            style="background: var(--color-primary); color: #ffffff"
            :title="auth.isAuthenticated ? 'Fitur ini akan tersedia di Phase 2' : 'Login terlebih dahulu untuk melamar'"
            @click="!auth.isAuthenticated && $router.push('/login')"
          >
            Lamar Sekarang
          </button>

          <p class="text-xs text-center mt-3" style="color: var(--color-text-secondary)">
            {{ auth.isAuthenticated ? 'Fitur lamaran tersedia dalam Phase 2.' : 'Masuk untuk melamar.' }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
