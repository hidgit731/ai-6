import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useNoteVersions, type NoteVersion } from '@/composables/useNoteVersions'
import { useNotesStore } from '@/stores/notes'

export const useNoteVersionsStore = defineStore('noteVersions', () => {
    const versionsApi = useNoteVersions()

    const versions = ref<NoteVersion[]>([])
    const hasMore = ref(false)
    const currentPage = ref(0)
    const isPanelOpen = ref(false)
    const loading = ref(false)
    const error = ref<string | null>(null)
    const selectedVersion = ref<NoteVersion | null>(null)

    const PER_PAGE = 20

    async function openPanel(noteId: string): Promise<void> {
        versions.value = []
        hasMore.value = false
        currentPage.value = 0
        selectedVersion.value = null
        error.value = null
        isPanelOpen.value = true
        await loadVersions(noteId, 1)
    }

    function closePanel(): void {
        isPanelOpen.value = false
    }

    async function loadVersions(noteId: string, page: number): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const result = await versionsApi.listVersions(noteId, page, PER_PAGE)
            versions.value = result.items
            currentPage.value = page
            hasMore.value = page < result.totalPages
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load versions'
        } finally {
            loading.value = false
        }
    }

    async function loadMore(noteId: string): Promise<void> {
        if (!hasMore.value || loading.value) return
        loading.value = true
        error.value = null
        try {
            const nextPage = currentPage.value + 1
            const result = await versionsApi.listVersions(noteId, nextPage, PER_PAGE)
            versions.value = [...versions.value, ...result.items]
            currentPage.value = nextPage
            hasMore.value = nextPage < result.totalPages
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to load more versions'
        } finally {
            loading.value = false
        }
    }

    async function selectVersion(noteId: string, version: NoteVersion): Promise<void> {
        selectedVersion.value = version
        error.value = null
        if (version.content === null && version.content !== undefined) {
            try {
                const full = await versionsApi.getVersion(noteId, version.id)
                selectedVersion.value = full
            } catch {
                // keep the partial version
            }
        }
    }

    async function revert(noteId: string, versionId: string): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const updatedNote = await versionsApi.revertVersion(noteId, versionId)
            const notesStore = useNotesStore()
            notesStore.currentNote = updatedNote
            selectedVersion.value = null
            await loadVersions(noteId, 1)
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Revert failed'
        } finally {
            loading.value = false
        }
    }

    return {
        versions,
        hasMore,
        currentPage,
        isPanelOpen,
        loading,
        error,
        selectedVersion,
        openPanel,
        closePanel,
        loadVersions,
        loadMore,
        selectVersion,
        revert,
    }
})
