import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { useNotes } from '@/composables/useNotes'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'
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
    const favorites = ref<NoteListItem[]>([])
    const favoritesPage = ref(1)
    const favoritesTotal = ref(0)
    const trash = ref<NoteListItem[]>([])
    const trashPage = ref(1)
    const trashTotal = ref(0)
    const isEmptyingTrash = ref(false)
    const loading = ref(false)
    const error = ref<string | null>(null)
    const lastError = ref<string | null>(null)
    const lastFailedNoteId = ref<string | null>(null)

    const foldersStore = useFoldersStore()
    const tagsStore = useTagsStore()

    watch(
        () => foldersStore.selectedFolderId,
        () => fetchList(1)
    )

    watch(
        () => tagsStore.selectedTagNames,
        () => fetchList(1)
    )

    async function fetchList(page = 1): Promise<void> {
        loading.value = true
        error.value = null
        try {
            const folderId = foldersStore.selectedFolderId
            const tagNames = tagsStore.selectedTagNames.length > 0 ? tagsStore.selectedTagNames : undefined
            const data = await notesApi.list(page, folderId !== null ? folderId : undefined, tagNames)
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

    async function toggleFavorite(noteId: string): Promise<void> {
        const idx = notes.value.findIndex((n) => n.id === noteId)
        const favIdx = favorites.value.findIndex((n) => n.id === noteId)

        const currentIsFavorite =
            idx !== -1 ? notes.value[idx].isFavorite : favIdx !== -1 ? favorites.value[favIdx].isFavorite : undefined

        if (currentIsFavorite === undefined) return

        if (idx !== -1) {
            notes.value[idx].isFavorite = !currentIsFavorite
        }

        let removedFav: (typeof favorites.value)[0] | undefined
        if (favIdx !== -1 && currentIsFavorite) {
            removedFav = favorites.value[favIdx]
            favorites.value.splice(favIdx, 1)
            favoritesTotal.value = Math.max(0, favoritesTotal.value - 1)
        }

        try {
            await notesApi.toggleFavorite(noteId)
        } catch (e) {
            if (idx !== -1) {
                notes.value[idx].isFavorite = currentIsFavorite
            }
            if (removedFav !== undefined) {
                favorites.value.splice(favIdx, 0, removedFav)
                favoritesTotal.value++
            }
            lastError.value = e instanceof Error ? e.message : 'Ошибка переключения избранного'
            lastFailedNoteId.value = noteId
            throw e
        }
    }

    async function softDeleteNote(noteId: string): Promise<void> {
        const idx = notes.value.findIndex((n) => n.id === noteId)
        const removedNote = idx !== -1 ? notes.value[idx] : null
        if (idx !== -1) {
            notes.value.splice(idx, 1)
        }

        const favIdx = favorites.value.findIndex((n) => n.id === noteId)
        const removedFav = favIdx !== -1 ? favorites.value[favIdx] : null
        if (favIdx !== -1) {
            favorites.value.splice(favIdx, 1)
            favoritesTotal.value = Math.max(0, favoritesTotal.value - 1)
        }

        try {
            await notesApi.softDelete(noteId)
        } catch (e) {
            if (removedNote !== null && idx !== -1) {
                notes.value.splice(idx, 0, removedNote)
            }
            if (removedFav !== null && favIdx !== -1) {
                favorites.value.splice(favIdx, 0, removedFav)
                favoritesTotal.value++
            }
            lastError.value = e instanceof Error ? e.message : 'Ошибка удаления'
            lastFailedNoteId.value = noteId
            throw e
        }
    }

    async function permanentDeleteNote(noteId: string): Promise<void> {
        const idx = trash.value.findIndex((n) => n.id === noteId)
        const removedNote = idx !== -1 ? trash.value[idx] : null

        if (idx !== -1) {
            trash.value.splice(idx, 1)
            trashTotal.value = Math.max(0, trashTotal.value - 1)
        }

        try {
            await notesApi.permanentDelete(noteId)
        } catch (e) {
            if (removedNote !== null && idx !== -1) {
                trash.value.splice(idx, 0, removedNote)
                trashTotal.value++
            }
            lastError.value = e instanceof Error ? e.message : 'Ошибка удаления'
            lastFailedNoteId.value = noteId
            throw e
        }
    }

    async function restoreNote(noteId: string): Promise<void> {
        const idx = trash.value.findIndex((n) => n.id === noteId)
        const restoredNote = idx !== -1 ? trash.value[idx] : null

        if (idx !== -1) {
            trash.value.splice(idx, 1)
            notes.value.unshift({ ...restoredNote!, deletedAt: null })
        }

        try {
            await notesApi.restore(noteId)
        } catch (e) {
            if (restoredNote && idx !== -1) {
                trash.value.splice(idx, 0, restoredNote)
            }
            const noteIdx = notes.value.findIndex((n) => n.id === noteId)
            if (noteIdx !== -1) {
                notes.value.splice(noteIdx, 1)
            }
            lastError.value = e instanceof Error ? e.message : 'Ошибка восстановления'
            lastFailedNoteId.value = noteId
            throw e
        }
    }

    async function fetchFavorites(page?: number): Promise<void> {
        const currentPage = page ?? favoritesPage.value
        try {
            const data = await notesApi.getFavorites(currentPage, 20)
            favorites.value = data.items
            favoritesPage.value = currentPage
            favoritesTotal.value = data.total
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка загрузки избранного'
            throw e
        }
    }

    async function fetchTrash(page?: number): Promise<void> {
        const currentPage = page ?? trashPage.value
        try {
            const data = await notesApi.getTrash(currentPage, 20)
            trash.value = data.items
            trashPage.value = currentPage
            trashTotal.value = data.total
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка загрузки корзины'
            throw e
        }
    }

    async function emptyAllTrash(): Promise<void> {
        isEmptyingTrash.value = true
        try {
            await notesApi.emptyTrash()
            trash.value = []
            trashTotal.value = 0
            trashPage.value = 1
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка очистки корзины'
            throw e
        } finally {
            isEmptyingTrash.value = false
        }
    }

    return {
        notes,
        currentNote,
        pagination,
        favorites,
        favoritesPage,
        favoritesTotal,
        trash,
        trashPage,
        trashTotal,
        isEmptyingTrash,
        loading,
        error,
        lastError,
        lastFailedNoteId,
        fetchList,
        fetchById,
        createNote,
        updateNote,
        deleteNote,
        moveNote,
        toggleFavorite,
        softDeleteNote,
        permanentDeleteNote,
        restoreNote,
        fetchFavorites,
        fetchTrash,
        emptyAllTrash,
    }
})
