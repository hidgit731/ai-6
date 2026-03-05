import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface ActivityPoint {
  date: string
  count: number
}

export interface DashboardStats {
  notes_count: number
  tags_count: number
  folders_count: number
  activity: ActivityPoint[]
}

export const useDashboardStore = defineStore('dashboard', () => {
  const stats = ref<DashboardStats | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  return { stats, loading, error }
})
