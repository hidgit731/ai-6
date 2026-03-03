import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { useFolders } from '@/composables/useFolders'
import type { FolderTreeNode, CreateFolderPayload, UpdateFolderPayload } from '@/composables/useFolders'

export const useFoldersStore = defineStore('folders', () => {
    const foldersApi = useFolders()

    const tree = ref<FolderTreeNode[]>([])
    const selectedFolderId = ref<string | null | 'none'>(null)
    const loading = ref(false)
    const error = ref<string | null>(null)
    const expandedIds = ref<Set<string>>(
        new Set(JSON.parse(localStorage.getItem('folder-expanded') ?? '[]') as string[])
    )

    watch(
        expandedIds,
        (val) => {
            localStorage.setItem('folder-expanded', JSON.stringify([...val]))
        },
        { deep: true }
    )

    function isExpanded(id: string): boolean {
        return expandedIds.value.has(id)
    }

    function toggleExpanded(id: string): void {
        if (expandedIds.value.has(id)) {
            expandedIds.value.delete(id)
        } else {
            expandedIds.value.add(id)
        }
        // Trigger reactivity by reassigning
        expandedIds.value = new Set(expandedIds.value)
    }

    async function fetchTree(): Promise<void> {
        loading.value = true
        error.value = null
        try {
            tree.value = await foldersApi.getTree()
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Ошибка загрузки папок'
        } finally {
            loading.value = false
        }
    }

    async function createFolder(payload: CreateFolderPayload): Promise<void> {
        await foldersApi.createFolder(payload)
        await fetchTree()
    }

    async function updateFolder(id: string, payload: UpdateFolderPayload): Promise<void> {
        await foldersApi.updateFolder(id, payload)
        await fetchTree()
    }

    async function deleteFolder(id: string, strategy?: string): Promise<void> {
        await foldersApi.deleteFolder(id, strategy)
        if (selectedFolderId.value === id) {
            selectedFolderId.value = null
        }
        await fetchTree()
    }

    async function moveFolder(folderId: string, targetParentId: string | null): Promise<void> {
        await foldersApi.moveFolder(folderId, targetParentId)
        await fetchTree()
    }

    function selectFolder(id: string | null | 'none'): void {
        selectedFolderId.value = id
    }

    return {
        tree,
        selectedFolderId,
        loading,
        error,
        expandedIds,
        isExpanded,
        toggleExpanded,
        fetchTree,
        createFolder,
        updateFolder,
        deleteFolder,
        moveFolder,
        selectFolder,
    }
})
