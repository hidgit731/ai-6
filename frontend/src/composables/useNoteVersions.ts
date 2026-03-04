const API_URL = import.meta.env.VITE_API_URL ?? '/api'

export interface NoteVersion {
    id: string
    noteId: string
    title: string
    content: string | null
    versionNumber: number
    createdAt: string
}

export interface PaginatedNoteVersions {
    items: NoteVersion[]
    total: number
    page: number
    perPage: number
    totalPages: number
}

export function useNoteVersions() {
    async function listVersions(
        noteId: string,
        page: number,
        limit: number,
    ): Promise<PaginatedNoteVersions> {
        const response = await fetch(
            `${API_URL}/notes/${noteId}/versions?page=${page}&limit=${limit}`,
        )
        if (!response.ok) {
            throw new Error(`Failed to fetch versions: ${response.status}`)
        }
        return response.json()
    }

    async function getVersion(noteId: string, versionId: string): Promise<NoteVersion> {
        const response = await fetch(`${API_URL}/notes/${noteId}/versions/${versionId}`)
        if (!response.ok) {
            throw new Error(`Failed to fetch version: ${response.status}`)
        }
        return response.json()
    }

    async function revertVersion(noteId: string, versionId: string): Promise<import('./useNotes').Note> {
        const response = await fetch(
            `${API_URL}/notes/${noteId}/versions/${versionId}/revert`,
            { method: 'POST' },
        )
        if (!response.ok) {
            const body = await response.json().catch(() => ({}))
            throw new Error(body.error ?? `Revert failed: ${response.status}`)
        }
        return response.json()
    }

    return { listVersions, getVersion, revertVersion }
}
