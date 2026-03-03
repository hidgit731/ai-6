<script setup lang="ts">
import { onMounted, ref } from 'vue'
import FolderTreeNode from './FolderTreeNode.vue'
import FolderContextMenu from './FolderContextMenu.vue'
import MoveFolderModal from './MoveFolderModal.vue'
import SkeletonList from './SkeletonList.vue'
import { useFoldersStore } from '@/stores/folders'
import { useToast } from '@/composables/useToast'

const foldersStore = useFoldersStore()
const { addToast } = useToast()

const contextMenuFolderId = ref<string | null>(null)
const contextMenuFolderName = ref('')
const moveFolderTargetId = ref<string | null>(null)

onMounted(async () => {
    try {
        await foldersStore.fetchTree()
    } catch {
        addToast('Ошибка загрузки папок', 'error')
    }
})

async function handleRetry(): Promise<void> {
    try {
        await foldersStore.fetchTree()
    } catch {
        addToast('Ошибка загрузки папок', 'error')
    }
}

function openContextMenu(id: string, event: MouseEvent): void {
    contextMenuFolderId.value = id
    const folder = findFolder(foldersStore.tree, id)
    contextMenuFolderName.value = folder?.name ?? ''
}

function findFolder(nodes: typeof foldersStore.tree, id: string): { name: string } | null {
    for (const node of nodes) {
        if (node.id === id) return node
        const found = findFolder(node.children, id)
        if (found) return found
    }
    return null
}

function openMoveModal(folderId: string): void {
    moveFolderTargetId.value = folderId
    contextMenuFolderId.value = null
}

async function handleCreateFolder(): Promise<void> {
    const name = prompt('Название новой папки:')
    if (!name?.trim()) return
    try {
        await foldersStore.createFolder({ name: name.trim(), parentId: null })
    } catch (e: unknown) {
        const msg = (typeof e === 'object' && e !== null && 'error' in e)
            ? String((e as { error: string }).error)
            : 'Ошибка создания папки'
        addToast(msg, 'error')
    }
}
</script>

<template>
    <nav class="folder-tree">
        <div class="folder-tree__header">Папки</div>

        <ul class="folder-tree__list">
            <li
                class="folder-tree__all"
                :class="{ 'folder-tree__all--active': foldersStore.selectedFolderId === null }"
                @click="foldersStore.selectFolder(null)"
            >
                Все заметки
            </li>
            <li
                class="folder-tree__all"
                :class="{ 'folder-tree__all--active': foldersStore.selectedFolderId === 'none' }"
                @click="foldersStore.selectFolder('none')"
            >
                Без папки
            </li>
        </ul>

        <SkeletonList v-if="foldersStore.loading" :count="5" />

        <div v-else-if="foldersStore.error" class="folder-tree__error">
            <span>{{ foldersStore.error }}</span>
            <button class="folder-tree__retry" @click="handleRetry">Повторить</button>
        </div>

        <ul v-else class="folder-tree__list folder-tree__tree">
            <FolderTreeNode
                v-for="node in foldersStore.tree"
                :key="node.id"
                :folder="node"
                :is-expanded="foldersStore.isExpanded(node.id)"
                :is-selected="foldersStore.selectedFolderId === node.id"
                @select="foldersStore.selectFolder($event)"
                @toggle="foldersStore.toggleExpanded($event)"
                @context-menu="openContextMenu"
            />
        </ul>

        <button class="folder-tree__create" @click="handleCreateFolder">+ Создать папку</button>

        <FolderContextMenu
            v-if="contextMenuFolderId"
            :folder-id="contextMenuFolderId"
            :folder-name="contextMenuFolderName"
            @close="contextMenuFolderId = null"
            @open-move-modal="openMoveModal"
        />

        <MoveFolderModal
            v-if="moveFolderTargetId"
            :source-folder-id="moveFolderTargetId"
            @confirm="async (targetId) => { await foldersStore.moveFolder(moveFolderTargetId!, targetId); moveFolderTargetId = null }"
            @cancel="moveFolderTargetId = null"
        />
    </nav>
</template>

<style scoped>
.folder-tree {
    width: 220px;
    min-width: 180px;
    border-right: 1px solid #e8e8e8;
    padding: 0.75rem 0;
    display: flex;
    flex-direction: column;
    position: relative;
}

.folder-tree__header {
    font-size: 0.75rem;
    font-weight: 600;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0 0.75rem 0.5rem;
}

.folder-tree__list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.folder-tree__all {
    padding: 0.35rem 0.75rem;
    font-size: 0.9rem;
    cursor: pointer;
    border-radius: 4px;
    color: #333;
}

.folder-tree__all:hover,
.folder-tree__all--active {
    background: #e8f0fe;
    color: #1a56db;
}

.folder-tree__tree {
    flex: 1;
    overflow-y: auto;
}

.folder-tree__error {
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
    color: #c33;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.folder-tree__retry {
    background: none;
    border: 1px solid #c33;
    color: #c33;
    border-radius: 4px;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    font-size: 0.8rem;
    align-self: flex-start;
}

.folder-tree__create {
    margin: 0.75rem 0.75rem 0;
    padding: 0.4rem;
    background: none;
    border: 1px dashed #ccc;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    color: #888;
    text-align: center;
}

.folder-tree__create:hover {
    border-color: #4a90d9;
    color: #4a90d9;
}
</style>
