<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeft, Save } from 'lucide-vue-next'
import AppLayout from '../../layouts/AppLayout.vue'
import { useJobs } from '../../composables/useJobs'
import type { AxiosError } from 'axios'

const route = useRoute()
const router = useRouter()
const { fetchJob, createJob, updateJob } = useJobs()

const jobId = computed(() => route.params.id as string | undefined)
const isEdit = computed(() => !!jobId.value)

const form = reactive({
  title: '',
  description: '',
  qualifications: '',
  location: '',
  deadline: '',
  status: 'draft' as 'draft' | 'active',
})

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

onMounted(async () => {
  if (!isEdit.value) return
  isLoading.value = true
  try {
    const job = await fetchJob(jobId.value!)
    form.title = job.title
    form.description = job.description ?? ''
    form.qualifications = job.qualifications ?? ''
    form.location = job.location ?? ''
    form.deadline = job.deadline ?? ''
    form.status = (job.status === 'active' ? 'active' : 'draft') as 'draft' | 'active'
  } catch {
    errorMessage.value = 'Gagal memuat data lowongan.'
  } finally {
    isLoading.value = false
  }
})

async function handleSubmit() {
  errorMessage.value = ''
  fieldErrors.value = {}
  isSaving.value = true

  try {
    if (isEdit.value) {
      await updateJob(jobId.value!, { ...form })
    } else {
      await createJob({ ...form })
    }
    router.push('/hr/jobs')
  } catch (err: unknown) {
    const axiosErr = err as AxiosError<{ error?: { message: string }; errors?: Record<string, string[]> }>
    if (axiosErr.response?.data?.errors) {
      fieldErrors.value = axiosErr.response.data.errors
    } else {
      errorMessage.value = axiosErr.response?.data?.error?.message ?? 'Gagal menyimpan lowongan.'
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto">
      <!-- Back -->
      <button @click="router.push('/hr/jobs')" class="inline-flex items-center gap-1.5 text-sm mb-6 transition-colors" style="color: var(--color-text-secondary)">
        <ArrowLeft :size="16" />
        Kembali
      </button>

      <h1 class="text-2xl font-bold mb-6" style="color: var(--color-text-primary)">
        {{ isEdit ? 'Edit Lowongan' : 'Buat Lowongan Baru' }}
      </h1>

      <!-- Loading skeleton -->
      <div v-if="isLoading" class="rounded-xl border p-8 animate-pulse" style="background: var(--color-surface); border-color: var(--color-border)">
        <div v-for="i in 5" :key="i" class="mb-6">
          <div class="h-3.5 w-1/5 rounded mb-2" style="background: var(--color-border)" />
          <div class="h-10 rounded-lg" style="background: var(--color-border)" />
        </div>
      </div>

      <div v-else class="rounded-xl border p-8" style="background: var(--color-surface); border-color: var(--color-border)">
        <!-- Top-level error -->
        <div v-if="errorMessage" class="rounded-lg px-4 py-3 mb-6 text-sm" style="background: color-mix(in srgb, var(--color-error) 10%, transparent); color: var(--color-error); border: 1px solid color-mix(in srgb, var(--color-error) 25%, transparent)">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleSubmit" novalidate class="space-y-5">
          <!-- Title -->
          <div>
            <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Judul Posisi <span style="color: var(--color-error)">*</span></label>
            <input v-model="form.title" type="text" required placeholder="cth. Software Engineer – Backend" class="w-full px-3 py-2.5 rounded-lg text-sm outline-none" style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)" />
            <p v-if="fieldErrors.title" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.title[0] }}</p>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Deskripsi Pekerjaan <span style="color: var(--color-error)">*</span></label>
            <textarea v-model="form.description" required rows="5" placeholder="Jelaskan tanggung jawab dan detail pekerjaan..." class="w-full px-3 py-2.5 rounded-lg text-sm outline-none resize-y" style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)" />
            <p v-if="fieldErrors.description" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.description[0] }}</p>
          </div>

          <!-- Qualifications -->
          <div>
            <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Kualifikasi <span style="color: var(--color-error)">*</span></label>
            <textarea v-model="form.qualifications" required rows="4" placeholder="Sebutkan persyaratan dan kualifikasi yang dibutuhkan..." class="w-full px-3 py-2.5 rounded-lg text-sm outline-none resize-y" style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)" />
            <p v-if="fieldErrors.qualifications" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.qualifications[0] }}</p>
          </div>

          <!-- Location + Deadline row -->
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Lokasi <span style="color: var(--color-error)">*</span></label>
              <input v-model="form.location" type="text" required placeholder="cth. Jakarta Selatan" class="w-full px-3 py-2.5 rounded-lg text-sm outline-none" style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)" />
              <p v-if="fieldErrors.location" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.location[0] }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Deadline <span style="color: var(--color-error)">*</span></label>
              <input v-model="form.deadline" type="date" required class="w-full px-3 py-2.5 rounded-lg text-sm outline-none" style="background: var(--color-background); border: 1px solid var(--color-border); color: var(--color-text-primary)" />
              <p v-if="fieldErrors.deadline" class="text-xs mt-1" style="color: var(--color-error)">{{ fieldErrors.deadline[0] }}</p>
            </div>
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium mb-1.5" style="color: var(--color-text-primary)">Status Publikasi</label>
            <div class="flex gap-3">
              <label v-for="opt in [{ value: 'draft', label: 'Draft' }, { value: 'active', label: 'Aktif (publik)' }]" :key="opt.value" class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.status" type="radio" :value="opt.value" class="accent-[var(--color-primary)]" />
                <span class="text-sm" style="color: var(--color-text-primary)">{{ opt.label }}</span>
              </label>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t" style="border-color: var(--color-border)">
            <button type="button" @click="router.push('/hr/jobs')" class="px-4 py-2 rounded-lg text-sm font-medium transition-colors" style="border: 1px solid var(--color-border); color: var(--color-text-secondary)">
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold transition-all"
              style="background: var(--color-primary); color: #ffffff"
              :class="{ 'opacity-70 cursor-not-allowed': isSaving }"
            >
              <span v-if="isSaving" class="inline-block h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
              <Save v-else :size="16" />
              {{ isSaving ? 'Menyimpan...' : (isEdit ? 'Simpan Perubahan' : 'Buat Lowongan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
