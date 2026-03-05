import { useNoteLinks } from '@/composables/useNoteLinks'
import type { GraphResponse } from '@/types/noteLinks'

export function useGraph() {
    async function getGraph(): Promise<GraphResponse> {
        return useNoteLinks().getGraph()
    }

    return { getGraph }
}
