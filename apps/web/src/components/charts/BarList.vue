<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface BarItem {
  /** Stable key (e.g. job id). */
  id: string
  label: string
  value: number
}

const props = withDefaults(
  defineProps<{
    items: BarItem[]
    /** CSS color (token var) for the bar fill. */
    color?: string
    emptyText?: string
  }>(),
  {
    color: 'var(--color-primary)',
    emptyText: 'Belum ada data.',
  },
)

// Reveal animation: bars grow from 0 → their width on mount. Subtle, and
// disabled under prefers-reduced-motion via the scoped CSS media query.
const revealed = ref(false)
onMounted(() => {
  revealed.value = true
})

const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)))

function widthPct(value: number): string {
  return `${(value / max.value) * 100}%`
}
</script>

<template>
  <ul v-if="items.length" class="bar-list">
    <li v-for="item in items" :key="item.id" class="bar-row">
      <span class="bar-row__label" :title="item.label">{{ item.label }}</span>
      <div class="bar-row__track" aria-hidden="true">
        <div
          class="bar-row__fill"
          :class="{ 'bar-row__fill--revealed': revealed }"
          :style="{ width: widthPct(item.value), background: color }"
        />
      </div>
      <span class="bar-row__value">{{ item.value }}</span>
    </li>
  </ul>
  <p v-else class="bar-list__empty">{{ emptyText }}</p>
</template>

<style scoped>
.bar-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.bar-row {
  display: grid;
  grid-template-columns: minmax(96px, 30%) 1fr auto;
  align-items: center;
  gap: 12px;
}

.bar-row__label {
  font-size: 0.8125rem;
  color: var(--color-text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.bar-row__track {
  height: 10px;
  border-radius: 9999px;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  overflow: hidden;
}

.bar-row__fill {
  height: 100%;
  border-radius: 9999px;
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 600ms cubic-bezier(0.16, 1, 0.3, 1);
}

.bar-row__fill--revealed {
  transform: scaleX(1);
}

.bar-row__value {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-text-primary);
  min-width: 1.5ch;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.bar-list__empty {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  margin: 0;
  font-style: italic;
}

@media (prefers-reduced-motion: reduce) {
  .bar-row__fill {
    transition: none;
    transform: scaleX(1);
  }
}
</style>
