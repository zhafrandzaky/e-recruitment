<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  label: string
  value: number | null
  unit?: string
  /** Lucide icon component. */
  icon?: Component
  /** Shown when value is null. */
  emptyHint?: string
}>()
</script>

<template>
  <div class="stat">
    <div class="stat__head">
      <component :is="icon" v-if="icon" :size="18" class="stat__icon" />
      <span class="stat__label">{{ label }}</span>
    </div>
    <div v-if="value !== null" class="stat__figure">
      <span class="stat__value">{{ value }}</span>
      <span v-if="unit" class="stat__unit">{{ unit }}</span>
    </div>
    <div v-else class="stat__empty">
      <span class="stat__value stat__value--muted">&mdash;</span>
      <span class="stat__empty-hint">{{ emptyHint ?? 'Belum ada data' }}</span>
    </div>
  </div>
</template>

<style scoped>
.stat {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat__head {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text-secondary);
}

.stat__icon {
  color: var(--color-primary);
}

.stat__label {
  font-size: 0.8125rem;
  font-weight: 500;
}

.stat__figure {
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.stat__value {
  font-size: 2.5rem;
  font-weight: 700;
  line-height: 1;
  color: var(--color-text-primary);
  font-variant-numeric: tabular-nums;
}

.stat__value--muted {
  color: var(--color-text-secondary);
}

.stat__unit {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.stat__empty {
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.stat__empty-hint {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  font-style: italic;
}
</style>
