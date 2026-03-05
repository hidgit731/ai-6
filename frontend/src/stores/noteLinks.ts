import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useNoteLinks } from '@/composables/useNoteLinks'
import type { NoteLinksResponse } from '@/types/noteLinks'

export const useNoteLinksStore = defineStore('noteLinks', () => {
    const currentNoteLinks = ref<NoteLinksResponse | null>(null)

    async function fetchLinks(noteId: string): Promise<void> {
        const api = useNoteLinks()
        currentNoteLinks.value = await api.getLinks(noteId)
    }

    function clearLinks(): void {
        currentNoteLinks.value = null
    }

    return { currentNoteLinks, fetchLinks, clearLinks }
})
