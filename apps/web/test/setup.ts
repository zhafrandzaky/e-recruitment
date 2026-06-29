import { vi } from 'vitest'

// Provide a minimal localStorage mock for test environments where it's missing
if (typeof localStorage === 'undefined' || localStorage === null) {
  const store: Record<string, string> = {}
  const localStorageMock = {
    getItem: (key: string) => store[key] ?? null,
    setItem: (key: string, value: string) => { store[key] = value },
    removeItem: (key: string) => { delete store[key] },
    clear: () => { Object.keys(store).forEach((k) => delete store[k]) },
    key: (i: number) => Object.keys(store)[i] ?? null,
    get length() { return Object.keys(store).length },
  }
  Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock, writable: true })
}

if (typeof window === 'undefined') {
  Object.defineProperty(globalThis, 'window', { value: globalThis, writable: true })
}

if (typeof document !== 'undefined' && !document.documentElement) {
  // happy-dom creates documentElement, but guard just in case
}

// Provide matchMedia mock
if (typeof window !== 'undefined' && !window.matchMedia) {
  Object.defineProperty(window, 'matchMedia', {
    value: vi.fn().mockImplementation((query: string) => ({
      matches: false,
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    })),
    writable: true,
  })
}
