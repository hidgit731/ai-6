import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { useNotes } from '@/composables/useNotes'
import { useFoldersStore } from '@/stores/folders'
import type { Note, NoteListItem, PaginatedNotes, CreateNotePayload, UpdateNotePayload } from '@/composables/useNotes'

export const useNotesStore = defineStore('notes', () => {
    const notesApi = useNotes()

    const notes = ref<NoteListItem[]>([])
    const currentNote = ref<Note | null>(null)
    const pagination = ref<Omit<PaginatedNotes, 'items'>>({
        page: 1,
        perPage: 10,
        total: 0,
        totalPages: 1,
    })
    const loading = ref(false)
    const error = ref<string | null>(null)

    const foldersStore = useFoldersStore()

    watch(
        () => foldersStore.selectedFolderId,
        () => fetchList(1)
    )

    async function fetchList(page = 1): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const folderId = foldersStore.selectedFolderId
            const data = await notesApi.list(page, folderId !== null ? folderId : undefined)
            notes.value = data.items
            pagination.value = {
                page: data.page,
                perPage: data.perPage,
                total: data.total,
                totalPages: data.totalPages,
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка загрузки'
        } finally {
            loading.value = false
        }
    }

    async function fetchById(id: string): Promise<void> {
        loading.value = true
        error.value = null
        currentNote.value = null
        try {
            currentNote.value = await notesApi.getById(id)
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка загрузки'
        } finally {
            loading.value = false
        }
    }

    async function createNote(payload: CreateNotePayload): Promise<Note> {
        loading.value = true
        error.value = null
        try {
            const note = await notesApi.create(payload)
            return note
        } finally {
            loading.value = false
        }
    }

    async function updateNote(id: string, payload: UpdateNotePayload): Promise<Note> {
        loading.value = true
        error.value = null
        try {
            const note = await notesApi.update(id, payload)
            return note
        } finally {
            loading.value = false
        }
    }

    async function deleteNote(id: string): Promise<void> {
        loading.value = true
        error.value = null
        try {
            await notesApi.remove(id)
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка удаления'
            throw e
        } finally {
            loading.value = false
        }
    }

    async function moveNote(noteId: string, folderId: string | null): Promise<void> {
        const note = await notesApi.moveNoteToFolder(noteId, folderId)
        const idx = notes.value.findIndex((n) => n.id === noteId)
        if (idx !== -1) {
            notes.value[idx] = { ...notes.value[idx], folderId: note.folderId }
        }
        if (currentNote.value?.id === noteId) {
            currentNote.value = note
        }
    }

    return {
        notes,
        currentNote,
        pagination,
        loading,
        error,
        fetchList,
        fetchById,
        createNote,
        updateNote,
        deleteNote,
        moveNote,
    }
})
