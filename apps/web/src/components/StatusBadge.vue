<script setup lang="ts">
import type { ApplicationStatus } from '../types'
import { computed } from 'vue'

const props = defineProps<{
  status: ApplicationStatus
}>()

const config = computed(() => {
  switch (props.status) {
    case 'pending':
      return { label: 'Menunggu', class: 'badge--pending' }
    case 'shortlisted':
      return { label: 'Lolos Seleksi Berkas', class: 'badge--shortlisted' }
    case 'rejected':
      return { label: 'Ditolak', class: 'badge--rejected' }
    case 'hired':
      return { label: 'Diterima', class: 'badge--hired' }
  }
})
</script>

<template>
  <span class="status-badge" :class="config.class">
    {{ config.label }}
  </span>
</template>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.5;
  white-space: nowrap;
}

.badge--pending {
  background: #fef3c7;
  color: #92400e;
}

.dark .badge--pending {
  background: #292112;
  color: #fcd34d;
}

.badge--shortlisted {
  background: var(--color-accent-subtle);
  color: var(--color-accent);
}

.badge--rejected {
  background: #fef2f2;
  color: var(--color-error);
}

.dark .badge--rejected {
  background: #2d1b1b;
  color: #fca5a5;
}

/* Hired ("Diterima") — strongest positive. Subtle bg derived from the token
   via color-mix so no extra hex is introduced (design-system §2, ADR-026). */
.badge--hired {
  background: color-mix(in srgb, var(--color-success-strong) 14%, transparent);
  color: var(--color-success-strong);
}
</style>
