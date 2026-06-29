<script setup lang="ts">
import { useTheme } from '../composables/useTheme'
import { Moon, Sun } from 'lucide-vue-next'

const { theme, toggleTheme } = useTheme()
</script>

<template>
  <div
    class="min-h-screen flex flex-col"
    style="background: var(--color-background)"
  >
    <!-- Header -->
    <header
      class="flex items-center justify-between px-6 py-4 border-b"
      style="border-color: var(--color-border)"
    >
      <RouterLink to="/jobs" class="flex items-center gap-2">
        <img
          :src="theme === 'dark' ? '/src/assets/logo/logo-light.svg' : '/src/assets/logo/logo-primary.svg'"
          alt="Logo"
          class="h-7 w-auto"
        />
      </RouterLink>

      <button
        @click="toggleTheme"
        class="p-2 rounded-md transition-colors"
        style="color: var(--color-text-secondary)"
        :aria-label="theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'"
      >
        <Moon v-if="theme === 'light'" :size="18" />
        <Sun v-else :size="18" />
      </button>
    </header>

    <!-- Main content centered -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
      <slot />
    </main>
  </div>
</template>
