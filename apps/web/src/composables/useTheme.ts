import { ref, watchEffect } from 'vue'

type Theme = 'light' | 'dark'

const STORAGE_KEY = 'theme'

function getSystemTheme(): Theme {
  if (typeof window === 'undefined') return 'light'
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function getStoredTheme(): Theme | null {
  if (typeof localStorage === 'undefined' || localStorage === null) return null
  const stored = localStorage.getItem(STORAGE_KEY)
  return stored === 'dark' || stored === 'light' ? stored : null
}

const theme = ref<Theme>(getStoredTheme() ?? getSystemTheme())

watchEffect(() => {
  if (typeof document === 'undefined') return
  const html = document.documentElement
  if (theme.value === 'dark') {
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
  }
  if (typeof localStorage !== 'undefined' && localStorage !== null) {
    localStorage.setItem(STORAGE_KEY, theme.value)
  }
})

export function useTheme() {
  function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark'
  }

  function setTheme(value: Theme) {
    theme.value = value
  }

  return { theme, toggleTheme, setTheme }
}
