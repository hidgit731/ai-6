export interface Folder {
    id: string
    name: string
    parentId: string | null
    createdAt: string
    updatedAt: string
}

export interface FolderTreeNode {
    id: string
    name: string
    parentId: string | null
    children: FolderTreeNode[]
}

export interface FolderTreeResponse {
    tree: FolderTreeNode[]
}

export interface CreateFolderPayload {
    name: string
    parentId: string | null
}

export interface UpdateFolderPayload {
    name: string
}

export interface MoveFolderPayload {
    targetParentId: string | null
}

const API_BASE = import.meta.env.VITE_API_URL ?? '/api'

export function useFolders() {
    async function getTree(): Promise<FolderTreeNode[]> {
        const res = await fetch(`${API_BASE}/folders/tree`)
        if (!res.ok) throw new Error('Ошибка загрузки дерева папок')
        const data: FolderTreeResponse = await res.json()
        return data.tree
    }

    async function createFolder(payload: CreateFolderPayload): Promise<Folder> {
        const res = await fetch(`${API_BASE}/folders`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        if (res.status === 409) {
            const data = await res.json()
            throw { status: 409, error: data.error }
        }
        if (res.status === 422) {
            const data = await res.json()
            throw { status: 422, errors: data.errors ?? data.error }
        }
        if (res.status === 404) {
            const data = await res.json()
            throw { status: 404, error: data.error }
        }
        if (!res.ok) throw new Error('Ошибка создания папки')
        return res.json()
    }

    async function updateFolder(id: string, payload: UpdateFolderPayload): Promise<Folder> {
        const res = await fetch(`${API_BASE}/folders/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
        if (res.status === 404) throw new Error('Папка не найдена.')
        if (res.status === 409) {
            const data = await res.json()
            throw { status: 409, error: data.error }
        }
        if (!res.ok) throw new Error('Ошибка обновления папки')
        return res.json()
    }

    async function deleteFolder(id: string, strategy?: string): Promise<void> {
        const url = strategy
            ? `${API_BASE}/folders/${id}?strategy=${strategy}`
            : `${API_BASE}/folders/${id}`
        const res = await fetch(url, { method: 'DELETE' })
        if (res.status === 204) return
        if (res.status === 404) throw new Error('Папка не найдена.')
        if (res.status === 400) {
            const data = await res.json()
            throw { status: 400, ...data }
        }
        if (!res.ok) throw new Error('Ошибка удаления папки')
    }

    async function moveFolder(folderId: string, targetParentId: string | null): Promise<Folder> {
        const res = await fetch(`${API_BASE}/folders/${folderId}/move`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ targetParentId }),
        })
        if (res.status === 404) throw new Error('Папка не найдена.')
        if (res.status === 409) {
            const data = await res.json()
            throw { status: 409, error: data.error }
        }
        if (res.status === 422) {
            const data = await res.json()
            throw { status: 422, error: data.error }
        }
        if (!res.ok) throw new Error('Ошибка перемещения папки')
        return res.json()
    }

    return { getTree, createFolder, updateFolder, deleteFolder, moveFolder }
}
