import { useDashboardStore } from '@/stores/dashboard'

export function useDashboard() {
  const store = useDashboardStore()

  async function fetchStats(): Promise<void> {
    store.loading = true
    store.error = null

    try {
      const response = await fetch('/api/dashboard')
      if (!response.ok) {
        throw new Error('Не удалось загрузить статистику')
      }
      store.stats = await response.json()
    } catch (error) {
      store.error = error instanceof Error ? error.message : 'Ошибка загрузки'
    } finally {
      store.loading = false
    }
  }

  return { fetchStats }
}
