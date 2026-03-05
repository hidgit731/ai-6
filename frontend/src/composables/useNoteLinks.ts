import type { NoteLinksResponse, GraphResponse } from '@/types/noteLinks'

const API_BASE = import.meta.env.VITE_API_URL ?? '/api'

export function useNoteLinks() {
    async function getLinks(noteId: string): Promise<NoteLinksResponse> {
        const res = await fetch(`${API_BASE}/notes/${noteId}/links`)
        if (res.status === 404) throw new Error('Заметка не найдена.')
        if (!res.ok) throw new Error('Ошибка загрузки ссылок')
        return res.json()
    }

    async function getGraph(): Promise<GraphResponse> {
        const res = await fetch(`${API_BASE}/graph`)
        if (!res.ok) throw new Error('Ошибка загрузки графа')
        return res.json()
    }

    return { getLinks, getGraph }
}
