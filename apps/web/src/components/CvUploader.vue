<script setup lang="ts">
import { ref } from 'vue'
import { Upload, FileText, AlertCircle, CheckCircle2 } from 'lucide-vue-next'

const emit = defineEmits<{
  (e: 'file-selected', file: File): void
  (e: 'file-cleared'): void
}>()

const props = defineProps<{
  error?: string | null
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const dragOver = ref(false)

const MAX_SIZE = 2 * 1024 * 1024 // 2MB

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) processFile(file)
}

function onDrop(event: DragEvent) {
  dragOver.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) processFile(file)
}

function processFile(file: File) {
  const ext = file.name.split('.').pop()?.toLowerCase()
  if (ext !== 'pdf') {
    emit('file-selected', file) // let parent validate
    selectedFile.value = file
    return
  }
  if (file.size > MAX_SIZE) {
    emit('file-selected', file)
    selectedFile.value = file
    return
  }
  selectedFile.value = file
  emit('file-selected', file)
}

function clearFile() {
  selectedFile.value = null
  emit('file-cleared')
  if (fileInput.value) fileInput.value.value = ''
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>

<template>
  <div class="cv-uploader">
    <label class="upload-label">CV (PDF, maks 2MB) <span class="required">*</span></label>

    <div
      class="drop-zone"
      :class="{ 'drop-zone--active': dragOver, 'drop-zone--error': error, 'drop-zone--has-file': selectedFile && !error }"
      @dragover.prevent="dragOver = true"
      @dragleave="dragOver = false"
      @drop.prevent="onDrop"
    >
      <input
        ref="fileInput"
        type="file"
        accept=".pdf"
        class="file-input"
        @change="onFileChange"
      />

      <div v-if="!selectedFile" class="drop-zone__placeholder">
        <Upload class="drop-zone__icon" :size="28" />
        <p class="drop-zone__text">
          <span class="drop-zone__link">Pilih file</span> atau seret ke sini
        </p>
        <p class="drop-zone__hint">PDF saja, maksimal 2MB</p>
      </div>

      <div v-else class="drop-zone__file-info">
        <div class="file-row">
          <FileText :size="20" class="file-row__icon" />
          <span class="file-row__name">{{ selectedFile.name }}</span>
          <span class="file-row__size">{{ formatSize(selectedFile.size) }}</span>
          <button type="button" class="file-row__remove" @click="clearFile" aria-label="Hapus file">
            &times;
          </button>
        </div>
        <div v-if="!error" class="file-row__status file-row__status--ok">
          <CheckCircle2 :size="14" />
          <span>File siap diunggah</span>
        </div>
      </div>

      <div v-if="error" class="drop-zone__error">
        <AlertCircle :size="16" />
        <span>{{ error }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cv-uploader {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.upload-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-primary);
}

.required {
  color: var(--color-error);
}

.drop-zone {
  position: relative;
  border: 2px dashed var(--color-border);
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  transition: border-color 0.15s, background-color 0.15s;
  cursor: pointer;
  background: var(--color-surface);
}

.drop-zone:hover {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
}

.drop-zone--active {
  border-color: var(--color-primary);
  background: var(--color-primary-subtle);
}

.drop-zone--error {
  border-color: var(--color-error);
  background: #fef2f2;
}

.drop-zone--has-file {
  border-style: solid;
  border-color: var(--color-border);
}

.file-input {
  position: absolute;
  inset: 0;
  opacity: 0;
  cursor: pointer;
}

.drop-zone__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.drop-zone__icon {
  color: var(--color-text-secondary);
}

.drop-zone__text {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.drop-zone__link {
  color: var(--color-primary);
  font-weight: 500;
  text-decoration: underline;
}

.drop-zone__hint {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin: 0;
}

.drop-zone__file-info {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.file-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.file-row__icon {
  color: var(--color-primary);
  flex-shrink: 0;
}

.file-row__name {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-row__size {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.file-row__remove {
  flex-shrink: 0;
  background: none;
  border: none;
  font-size: 1.25rem;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0 4px;
  line-height: 1;
}

.file-row__remove:hover {
  color: var(--color-error);
}

.file-row__status {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
}

.file-row__status--ok {
  color: var(--color-accent);
}

.drop-zone__error {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 8px;
  font-size: 0.8125rem;
  color: var(--color-error);
}
</style>
