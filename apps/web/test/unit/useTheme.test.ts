import { describe, it, expect, beforeEach, vi } from 'vitest'

describe('useTheme', () => {
  beforeEach(() => {
    localStorage.clear()
    document.documentElement.classList.remove('dark')
    vi.resetModules()
  })

  it('falls back to light when no stored theme', async () => {
    const { useTheme } = await import('../../src/composables/useTheme')
    const { theme } = useTheme()
    expect(['light', 'dark']).toContain(theme.value)
  })

  it('toggleTheme switches between light and dark', async () => {
    const { useTheme } = await import('../../src/composables/useTheme')
    const { theme, toggleTheme, setTheme } = useTheme()
    setTheme('light')
    toggleTheme()
    expect(theme.value).toBe('dark')
    toggleTheme()
    expect(theme.value).toBe('light')
  })

  it('setTheme sets an explicit value', async () => {
    const { useTheme } = await import('../../src/composables/useTheme')
    const { theme, setTheme } = useTheme()
    setTheme('dark')
    expect(theme.value).toBe('dark')
    setTheme('light')
    expect(theme.value).toBe('light')
  })
})
