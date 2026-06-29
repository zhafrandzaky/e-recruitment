import { ref, watchEffect } from 'vue'

type Theme = 'light' | 'dark'

const STORAGE_KEY = 'theme'

function getSystemTheme(): Theme {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function getStoredTheme(): Theme | null {
  const stored = localStorage.getItem(STORAGE_KEY)
  return stored === 'dark' || stored === 'light' ? stored : null
}

const theme = ref<Theme>(getStoredTheme() ?? getSystemTheme())

watchEffect(() => {
  const html = document.documentElement
  if (theme.value === 'dark') {
    html.classList.add('dark')
  } else {
    html.classList.remove('dark')
  }
  localStorage.setItem(STORAGE_KEY, theme.value)
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
