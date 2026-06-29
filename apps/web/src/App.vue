<script setup lang="ts">
import { useTheme } from './composables/useTheme'
import { Moon, Sun } from 'lucide-vue-next'

const { theme, toggleTheme } = useTheme()
</script>

<template>
  <div class="min-h-screen flex flex-col items-center justify-center gap-6 px-4">
    <img
      :src="theme === 'dark' ? '/assets/logo/logo-light.svg' : '/assets/logo/logo-primary.svg'"
      alt="e-recruitment logo"
      class="h-12 w-auto"
    />

    <h1 class="text-2xl font-semibold" style="color: var(--color-text-primary)">
      e-recruitment
    </h1>

    <p class="text-sm" style="color: var(--color-text-secondary)">
      Infrastructure ready. Feature development begins in Phase 1.
    </p>

    <button
      @click="toggleTheme"
      class="flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors"
      style="background: var(--color-surface); color: var(--color-text-primary); border: 1px solid var(--color-border);"
    >
      <Moon v-if="theme === 'light'" :size="16" />
      <Sun v-else :size="16" />
      {{ theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode' }}
    </button>
  </div>
</template>
