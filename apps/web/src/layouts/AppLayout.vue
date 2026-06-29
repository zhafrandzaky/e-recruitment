<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { Briefcase, LogOut, Moon, Sun, Plus, List } from 'lucide-vue-next'
import { useTheme } from '../composables/useTheme'
import { useAuthStore } from '../stores/auth'

const { theme, toggleTheme } = useTheme()
const auth = useAuthStore()
const router = useRouter()

const navLinks = computed(() => {
  if (auth.isHrAdmin) {
    return [
      { to: '/hr/jobs', label: 'Kelola Lowongan', icon: List },
      { to: '/hr/jobs/create', label: 'Buat Lowongan', icon: Plus },
    ]
  }
  return [
    { to: '/jobs', label: 'Lowongan', icon: Briefcase },
  ]
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen flex flex-col" style="background: var(--color-background)">
    <!-- Navbar -->
    <header
      class="sticky top-0 z-10 border-b"
      style="background: var(--color-background); border-color: var(--color-border)"
    >
      <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14">
        <!-- Logo -->
        <RouterLink to="/jobs" class="flex items-center gap-2 shrink-0">
          <img
            :src="theme === 'dark' ? '/src/assets/logo/logo-light.svg' : '/src/assets/logo/logo-primary.svg'"
            alt="Logo"
            class="h-7 w-auto"
          />
        </RouterLink>

        <!-- Nav links -->
        <nav class="hidden sm:flex items-center gap-1">
          <RouterLink
            v-for="link in navLinks"
            :key="link.to"
            :to="link.to"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
            :style="{
              color: 'var(--color-text-secondary)',
            }"
            active-class="text-[var(--color-primary)] bg-[var(--color-primary-subtle)]"
          >
            <component :is="link.icon" :size="16" />
            {{ link.label }}
          </RouterLink>
        </nav>

        <!-- Right side -->
        <div class="flex items-center gap-2">
          <button
            @click="toggleTheme"
            class="p-2 rounded-md transition-colors"
            style="color: var(--color-text-secondary)"
            :aria-label="theme === 'light' ? 'Dark mode' : 'Light mode'"
          >
            <Moon v-if="theme === 'light'" :size="16" />
            <Sun v-else :size="16" />
          </button>

          <template v-if="auth.isAuthenticated">
            <span class="text-sm hidden md:block" style="color: var(--color-text-secondary)">
              {{ auth.user?.name }}
            </span>
            <button
              @click="handleLogout"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
              style="color: var(--color-text-secondary)"
            >
              <LogOut :size="16" />
              <span class="hidden sm:inline">Keluar</span>
            </button>
          </template>

          <RouterLink
            v-else
            to="/login"
            class="px-4 py-1.5 rounded-md text-sm font-semibold transition-colors"
            style="background: var(--color-primary); color: #ffffff"
          >
            Masuk
          </RouterLink>
        </div>
      </div>
    </header>

    <!-- Page content -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 py-8">
      <slot />
    </main>

    <!-- Footer -->
    <footer
      class="border-t py-6 text-center text-sm"
      style="border-color: var(--color-border); color: var(--color-text-secondary)"
    >
      &copy; {{ new Date().getFullYear() }} e-recruitment
    </footer>
  </div>
</template>
