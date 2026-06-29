<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import type { StatusFunnel, ApplicationStatus } from '../../types'

const props = defineProps<{
  funnel: StatusFunnel
}>()

// Pipeline order: progression first, drop-off (rejected) last. Each stage maps
// to its semantic design token (DESIGN-SYSTEM.md §2; hired = --color-success-strong).
const STAGES: { status: ApplicationStatus; label: string; color: string }[] = [
  { status: 'pending', label: 'Menunggu', color: 'var(--color-warning)' },
  { status: 'shortlisted', label: 'Lolos Seleksi Berkas', color: 'var(--color-accent)' },
  { status: 'hired', label: 'Diterima', color: 'var(--color-success-strong)' },
  { status: 'rejected', label: 'Ditolak', color: 'var(--color-error)' },
]

const revealed = ref(false)
onMounted(() => {
  revealed.value = true
})

const total = computed(() => STAGES.reduce((sum, s) => sum + (props.funnel[s.status] ?? 0), 0))
const max = computed(() => Math.max(1, ...STAGES.map((s) => props.funnel[s.status] ?? 0)))

function widthPct(value: number): string {
  return `${(value / max.value) * 100}%`
}

function sharePct(value: number): string {
  if (total.value === 0) return '0%'
  return `${Math.round((value / total.value) * 100)}%`
}
</script>

<template>
  <div class="funnel">
    <div v-for="stage in STAGES" :key="stage.status" class="funnel-stage">
      <div class="funnel-stage__head">
        <span class="funnel-stage__label">
          <span class="funnel-stage__dot" :style="{ background: stage.color }" aria-hidden="true" />
          {{ stage.label }}
        </span>
        <span class="funnel-stage__count">
          {{ funnel[stage.status] ?? 0 }}
          <span class="funnel-stage__share">({{ sharePct(funnel[stage.status] ?? 0) }})</span>
        </span>
      </div>
      <div class="funnel-stage__track" aria-hidden="true">
        <div
          class="funnel-stage__fill"
          :class="{ 'funnel-stage__fill--revealed': revealed }"
          :style="{ width: widthPct(funnel[stage.status] ?? 0), background: stage.color }"
        />
      </div>
    </div>
    <p class="funnel__total">Total lamaran: <strong>{{ total }}</strong></p>
  </div>
</template>

<style scoped>
.funnel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.funnel-stage__head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
}

.funnel-stage__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8125rem;
  color: var(--color-text-primary);
}

.funnel-stage__dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  flex-shrink: 0;
}

.funnel-stage__count {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}

.funnel-stage__share {
  font-weight: 400;
  color: var(--color-text-secondary);
  font-size: 0.75rem;
}

.funnel-stage__track {
  height: 12px;
  border-radius: 9999px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.funnel-stage__fill {
  height: 100%;
  border-radius: 9999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 600ms cubic-bezier(0.16, 1, 0.3, 1);
}

.funnel-stage__fill--revealed {
  transform: scaleX(1);
}

.funnel__total {
  margin: 4px 0 0;
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
}

@media (prefers-reduced-motion: reduce) {
  .funnel-stage__fill {
    transition: none;
    transform: scaleX(1);
  }
}
</style>
