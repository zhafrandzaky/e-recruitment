<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Search, MapPin, Calendar, ChevronRight, Briefcase } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import { useJobs } from '../../composables/useJobs'

const router = useRouter()
const { jobs, meta, isLoading, error, fetchJobs } = useJobs()

const searchQuery = ref('')
const debouncedSearch = ref('')
let debounceTimer: ReturnType<typeof setTimeout>

function onSearchInput() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    debouncedSearch.value = searchQuery.value
    fetchJobs(debouncedSearch.value, 1)
  }, 350)
}

function formatDeadline(deadline: string | null): string {
  if (!deadline) return '-'
  return new Date(deadline).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(() => fetchJobs())
</script>

<template>
  <AppLayout>
    <!-- Header section -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold mb-1" style="color: var(--color-text-primary)">Lowongan Pekerjaan</h1>
      <p class="text-sm" style="color: var(--color-text-secondary)">
        Temukan peluang karir yang sesuai dengan Anda
      </p>
    </div>

    <!-- Search bar -->
    <div class="relative mb-6 max-w-xl">
      <Search :size="18" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary)" />
      <input
        v-model="searchQuery"
        @input="onSearchInput"
        type="search"
        placeholder="Cari posisi atau kata kunci..."
        class="w-full pl-10 pr-4 py-2.5 rounded-lg text-sm outline-none transition-shadow"
        style="background: var(--color-surface); border: 1px solid var(--color-border); color: var(--color-text-primary)"
      />
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex flex-col gap-3">
      <div
        v-for="i in 5"
        :key="i"
        class="rounded-xl border p-5 animate-pulse"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <div class="h-5 rounded-md mb-3 w-2/5" style="background: var(--color-border)" />
        <div class="h-3.5 rounded mb-2 w-1/4" style="background: var(--color-border)" />
        <div class="h-3.5 rounded w-1/5" style="background: var(--color-border)" />
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="rounded-xl border p-8 text-center" style="border-color: var(--color-border)">
      <p style="color: var(--color-error)">{{ error }}</p>
      <button @click="fetchJobs()" class="mt-3 text-sm underline" style="color: var(--color-primary)">Coba lagi</button>
    </div>

    <!-- Empty state -->
    <div v-else-if="!jobs.length" class="rounded-xl border p-12 text-center" style="background: var(--color-surface); border-color: var(--color-border)">
      <Briefcase :size="48" class="mx-auto mb-4" style="color: var(--color-text-secondary)" />
      <p class="font-medium" style="color: var(--color-text-primary)">Tidak ada lowongan ditemukan</p>
      <p class="text-sm mt-1" style="color: var(--color-text-secondary)">
        {{ searchQuery ? 'Coba kata kunci yang berbeda.' : 'Belum ada lowongan tersedia saat ini.' }}
      </p>
    </div>

    <!-- Job list -->
    <TransitionGroup
      v-else
      tag="div"
      name="job-list"
      class="flex flex-col gap-3"
    >
      <button
        v-for="job in jobs"
        :key="job.id"
        @click="router.push(`/jobs/${job.id}`)"
        class="rounded-xl border p-5 text-left w-full transition-all duration-200 group"
        style="background: var(--color-surface); border-color: var(--color-border)"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <h2
              class="font-semibold text-base mb-2 group-hover:underline truncate transition-colors"
              style="color: var(--color-primary)"
            >
              {{ job.title }}
            </h2>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
              <span v-if="job.location" class="flex items-center gap-1 text-sm" style="color: var(--color-text-secondary)">
                <MapPin :size="13" />
                {{ job.location }}
              </span>
              <span v-if="job.deadline" class="flex items-center gap-1 text-sm" style="color: var(--color-text-secondary)">
                <Calendar :size="13" />
                Deadline: {{ formatDeadline(job.deadline) }}
              </span>
            </div>
          </div>
          <ChevronRight :size="18" class="shrink-0 mt-0.5 transition-transform duration-200 group-hover:translate-x-0.5" style="color: var(--color-text-secondary)" />
        </div>
      </button>
    </TransitionGroup>

    <!-- Pagination -->
    <div v-if="meta.total > meta.per_page" class="flex items-center justify-between mt-8 pt-6 border-t" style="border-color: var(--color-border)">
      <p class="text-sm" style="color: var(--color-text-secondary)">
        Menampilkan {{ Math.min(meta.page * meta.per_page, meta.total) }} dari {{ meta.total }} lowongan
      </p>
      <div class="flex gap-2">
        <button
          :disabled="meta.page <= 1"
          @click="fetchJobs(debouncedSearch, meta.page - 1)"
          class="px-3 py-1.5 rounded-md text-sm transition-colors"
          style="border: 1px solid var(--color-border); color: var(--color-text-secondary)"
          :class="{ 'opacity-40 cursor-not-allowed': meta.page <= 1 }"
        >
          Sebelumnya
        </button>
        <button
          :disabled="meta.page * meta.per_page >= meta.total"
          @click="fetchJobs(debouncedSearch, meta.page + 1)"
          class="px-3 py-1.5 rounded-md text-sm transition-colors"
          style="border: 1px solid var(--color-border); color: var(--color-text-secondary)"
          :class="{ 'opacity-40 cursor-not-allowed': meta.page * meta.per_page >= meta.total }"
        >
          Selanjutnya
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.job-list-move,
.job-list-enter-active,
.job-list-leave-active {
  transition: all 0.25s ease;
}
.job-list-enter-from,
.job-list-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
