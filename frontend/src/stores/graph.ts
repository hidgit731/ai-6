import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useGraph } from '@/composables/useGraph'
import type { GraphResponse } from '@/types/noteLinks'

export const useGraphStore = defineStore('graph', () => {
    const graphData = ref<GraphResponse | null>(null)
    const loading = ref(false)

    async function fetchGraph(): Promise<void> {
        loading.value = true
        try {
            graphData.value = await useGraph().getGraph()
        } finally {
            loading.value = false
        }
    }

    return { graphData, loading, fetchGraph }
})
