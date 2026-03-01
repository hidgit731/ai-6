import { readonly, ref } from 'vue'
import type { Ref } from 'vue'

/**
 * Контракт для асинхронных composables (из contracts/composables-contract.md).
 * Все composables, делающие HTTP-запросы, MUST возвращать этот интерфейс.
 */
interface AsyncComposableReturn<T> {
  data: Readonly<Ref<T | null>>
  error: Readonly<Ref<string | null>>
  loading: Readonly<Ref<boolean>>
}

/**
 * Пример composable — шаблон для всех API-вызовов приложения.
 *
 * Правила (Конституция):
 * - API-вызовы ТОЛЬКО через composables, не из компонентов напрямую
 * - Composable именуется: use<Resource><Action?>
 * - Типизировать ответы через TypeScript-интерфейсы
 */
export function useExample(): AsyncComposableReturn<string> & { fetch: () => Promise<void> } {
  const data = ref<string | null>(null)
  const error = ref<string | null>(null)
  const loading = ref<boolean>(false)

  async function fetch(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      // В реальных composables: обращение к backend_monolith REST API
      // const apiBase = import.meta.env.VITE_API_URL ?? '/api'
      // const response = await window.fetch(`${apiBase}/resource`)
      // if (!response.ok) throw new Error(`HTTP ${response.status}`)
      // data.value = await response.json() as ResourceType
      data.value = 'example response'
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
    } finally {
      loading.value = false
    }
  }

  return {
    data: readonly(data),
    error: readonly(error),
    loading: readonly(loading),
    fetch,
  }
}
