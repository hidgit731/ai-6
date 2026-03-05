import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDashboardStore } from './dashboard'
import { useDashboard } from '@/composables/useDashboard'

const mockStats = {
  notes_count: 42,
  tags_count: 15,
  folders_count: 8,
  activity: Array.from({ length: 30 }, (_, i) => ({
    date: `2026-02-${String(i + 1).padStart(2, '0')}`,
    count: i,
  })),
}

describe('dashboard store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.restoreAllMocks()
  })

  it('starts with null stats, no loading, no error', () => {
    const store = useDashboardStore()
    expect(store.stats).toBeNull()
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchStats populates stats on success', async () => {
    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({
      ok: true,
      json: () => Promise.resolve(mockStats),
    })))

    const { fetchStats } = useDashboard()
    await fetchStats()

    const store = useDashboardStore()
    expect(store.stats).toEqual(mockStats)
    expect(store.loading).toBe(false)
    expect(store.error).toBeNull()
  })

  it('fetchStats sets loading to true during fetch and false after', async () => {
    let resolveJson: ((v: unknown) => void) | null = null
    vi.stubGlobal('fetch', vi.fn(() => {
      const jsonPromise = new Promise(r => { resolveJson = r })
      return Promise.resolve({ ok: true, json: () => jsonPromise })
    }))

    const { fetchStats } = useDashboard()
    const store = useDashboardStore()

    const task = fetchStats()
    // Yield to let the fetch mock start
    await Promise.resolve()
    expect(store.loading).toBe(true)

    resolveJson!(mockStats)
    await task

    expect(store.loading).toBe(false)
  })

  it('fetchStats sets error on network failure', async () => {
    vi.stubGlobal('fetch', vi.fn(() => Promise.reject(new Error('Network error'))))

    const { fetchStats } = useDashboard()
    await fetchStats()

    const store = useDashboardStore()
    expect(store.error).toBe('Network error')
    expect(store.stats).toBeNull()
    expect(store.loading).toBe(false)
  })

  it('fetchStats sets error on non-ok response', async () => {
    vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: false, status: 500 })))

    const { fetchStats } = useDashboard()
    await fetchStats()

    const store = useDashboardStore()
    expect(store.error).toBeTruthy()
    expect(store.loading).toBe(false)
  })
})
