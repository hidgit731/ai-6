import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useSearch } from '@/composables/useSearch'
import type { SearchResultItem } from '@/composables/useSearch'

export const useSearchStore = defineStore('search', () => {
    const searchApi = useSearch()

    const query = ref('')
    const results = ref<SearchResultItem[]>([])
    const total = ref(0)
    const page = ref(1)
    const totalPages = ref(1)
    const loading = ref(false)
    const error = ref<string | null>(null)

    async function fetchResults(q: string, p = 1): Promise<void> {
        query.value = q
        loading.value = true
        error.value = null
        try {
            const data = await searchApi.search(q, p)
            results.value = data.items
            total.value = data.total
            page.value = data.page
            totalPages.value = data.totalPages
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Search error'
        } finally {
            loading.value = false
        }
    }

    async function fetchPage(p: number): Promise<void> {
        await fetchResults(query.value, p)
    }

    function clearResults(): void {
        query.value = ''
        results.value = []
        total.value = 0
        page.value = 1
        totalPages.value = 1
        loading.value = false
        error.value = null
    }

    return {
        query,
        results,
        total,
        page,
        totalPages,
        loading,
        error,
        fetchResults,
        fetchPage,
        clearResults,
    }
})
