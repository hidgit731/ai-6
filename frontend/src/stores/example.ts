import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

/**
 * Пример Pinia store — шаблон для всех stores приложения.
 *
 * Правила (Конституция):
 * - Состояние ТОЛЬКО через Pinia store, не в компонентах напрямую
 * - Composition API style (не Options API)
 * - Прямые HTTP-запросы внутри store ЗАПРЕЩЕНЫ — делегировать в composables
 */
export const useExampleStore = defineStore('example', () => {
  // --- State ---
  const count = ref<number>(0)

  // --- Getters ---
  const doubled = computed(() => count.value * 2)
  const isZero = computed(() => count.value === 0)

  // --- Actions ---
  function increment(): void {
    count.value++
  }

  function decrement(): void {
    count.value--
  }

  function reset(): void {
    count.value = 0
  }

  return { count, doubled, isZero, increment, decrement, reset }
})
