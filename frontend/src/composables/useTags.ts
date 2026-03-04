const API_BASE = import.meta.env.VITE_API_URL ?? '/api'

export interface Tag {
    id: string
    name: string
    createdAt: string
}

export interface TagCloudItem {
    id: string
    name: string
    noteCount: number
    createdAt: string
}

export function useTags() {
    async function list(): Promise<TagCloudItem[]> {
        const res = await fetch(`${API_BASE}/tags`)
        if (!res.ok) throw new Error('Ошибка загрузки тегов')
        return res.json()
    }

    async function suggest(q: string): Promise<Tag[]> {
        const res = await fetch(`${API_BASE}/tags/suggest?q=${encodeURIComponent(q)}`)
        if (!res.ok) throw new Error('Ошибка поиска тегов')
        return res.json()
    }

    async function create(name: string): Promise<TagCloudItem> {
        const res = await fetch(`${API_BASE}/tags`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name }),
        })
        if (!res.ok) throw new Error('Ошибка создания тега')
        return res.json()
    }

    // Pure API function — no side-effects.
    // For reactive update after deletion, use tagsStore.deleteTag() instead.
    async function remove(id: string): Promise<void> {
        const res = await fetch(`${API_BASE}/tags/${id}`, { method: 'DELETE' })
        if (res.status === 404) throw new Error('Тег не найден.')
        if (!res.ok) throw new Error('Ошибка удаления тега')
    }

    return { list, suggest, create, remove }
}
