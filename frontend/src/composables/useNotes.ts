import type { Tag } from '@/composables/useTags'

export interface NoteListItem {
    id: string
    title: string
    preview: string | null
    createdAt: string
    folderId: string | null
    tags: Tag[]
    isFavorite?: boolean
    deletedAt?: string | null
}

export interface Note {
    id: string
    title: string
    content: string | null
    createdAt: string
    updatedAt: string
    folderId: string | null
    folderName: string | null
    tags: Tag[]
    isFavorite?: boolean
    deletedAt?: string | null
}

export interface PaginatedNotes {
    items: NoteListItem[]
    page: number
    perPage: number
    total: number
    totalPages: number
}

export interface CreateNotePayload {
    title: string
    content: string | null
    folderId?: string | null
    tags?: string[]
}

export interface UpdateNotePayload {
    title: string
    content: string | null
    folderId?: string | null
    tags?: string[]
}

const API_BASE = import.meta.env.VITE_API_URL ?? '/api'

export function useNotes() {
    async function list(page = 1, folderId?: string | null, tagNames?: string[]): Promise<PaginatedNotes> {
        let url = `${API_BASE}/notes?page=${page}`
        if (folderId !== undefined) {
            url += `&folderId=${folderId ?? ''}`
        }
        if (tagNames && tagNames.length > 0) {
            for (const name of tagNames) {
                url += `&tags[]=${encodeURIComponent(name)}`
            }
        }
        const res = await fetch(url)
        if (!res.ok) throw new Error('Ошибка загрузки заметок')
        return res.json()
    }

    async function getById(id: string): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes/${id}`)
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (!res.ok) throw new Error('Ошибка загрузки заметки')
        return res.json()
    }

    async function create(payload: CreateNotePayload): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        if (res.status === 422) {
            const data = await res.json()
            throw { status: 422, errors: data.errors }
        }
        if (!res.ok) throw new Error('Ошибка создания заметки')
        return res.json()
    }

    async function update(id: string, payload: UpdateNotePayload): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        if (res.status === 422) {
            const data = await res.json()
            throw { status: 422, errors: data.errors }
        }
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (!res.ok) throw new Error('Ошибка обновления заметки')
        return res.json()
    }

    async function remove(id: string): Promise<void> {
        const res = await fetch(`${API_BASE}/notes/${id}`, { method: 'DELETE' })
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (!res.ok) throw new Error('Ошибка удаления заметки')
    }

    async function moveNoteToFolder(noteId: string, folderId: string | null): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes/${noteId}/folder`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ folderId }),
        })
        if (res.status === 404) throw new Error('Заметка или папка не найдена.')
        if (!res.ok) throw new Error('Ошибка перемещения заметки')
        return res.json()
    }

    async function toggleFavorite(noteId: string): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes/${noteId}/favorite`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        })
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (res.status === 409) throw new Error('Невозможно отметить удаленную заметку.')
        if (!res.ok) throw new Error('Ошибка переключения избранного')
        return res.json()
    }

    async function softDelete(noteId: string): Promise<void> {
        const res = await fetch(`${API_BASE}/notes/${noteId}`, {
            method: 'DELETE',
        })
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (res.status === 409) throw new Error('Заметка уже удалена.')
        if (!res.ok) throw new Error('Ошибка удаления заметки')
    }

    async function permanentDelete(noteId: string): Promise<void> {
        const res = await fetch(`${API_BASE}/notes/${noteId}/permanent`, {
            method: 'DELETE',
        })
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (res.status === 409) throw new Error('Заметка не в корзине.')
        if (!res.ok) throw new Error('Ошибка безвозвратного удаления')
    }

    async function restore(noteId: string): Promise<Note> {
        const res = await fetch(`${API_BASE}/notes/${noteId}/restore`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        })
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (res.status === 409) throw new Error('Заметка не удалена.')
        if (!res.ok) throw new Error('Ошибка восстановления заметки')
        return res.json()
    }

    async function getFavorites(page = 1, limit = 20): Promise<PaginatedNotes> {
        const res = await fetch(`${API_BASE}/notes/favorites?page=${page}&limit=${limit}`)
        if (!res.ok) throw new Error('Ошибка загрузки избранного')
        return res.json()
    }

    async function getTrash(page = 1, limit = 20): Promise<PaginatedNotes> {
        const res = await fetch(`${API_BASE}/notes/trash?page=${page}&limit=${limit}`)
        if (!res.ok) throw new Error('Ошибка загрузки корзины')
        return res.json()
    }

    return {
        list,
        getById,
        create,
        update,
        remove,
        moveNoteToFolder,
        toggleFavorite,
        softDelete,
        permanentDelete,
        restore,
        getFavorites,
        getTrash,
    }
}
