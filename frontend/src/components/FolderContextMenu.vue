<script setup lang="ts">
import { ref } from 'vue'
import DeleteFolderDialog from './DeleteFolderDialog.vue'
import { useFoldersStore } from '@/stores/folders'

const props = defineProps<{
    folderId: string
    folderName: string
}>()

const emit = defineEmits<{
    close: []
    openMoveModal: [folderId: string]
}>()

const foldersStore = useFoldersStore()

const isRenaming = ref(false)
const renameValue = ref(props.folderName)
const renameError = ref<string | null>(null)

const deleteDialogOpen = ref(false)
const deleteConflict = ref<{ hasNotes: boolean; hasFolders: boolean } | null>(null)

async function startRename(): Promise<void> {
    isRenaming.value = true
    renameValue.value = props.folderName
}

async function confirmRename(): Promise<void> {
    renameError.value = null
    try {
        await foldersStore.updateFolder(props.folderId, { name: renameValue.value })
        isRenaming.value = false
        emit('close')
    } catch (e: unknown) {
        if (typeof e === 'object' && e !== null && 'status' in e && (e as { status: number }).status === 409) {
            renameError.value = (e as { error: string }).error
        } else {
            renameError.value = 'Ошибка переименования'
        }
    }
}

async function requestDelete(): Promise<void> {
    try {
        await foldersStore.deleteFolder(props.folderId)
        emit('close')
    } catch (e: unknown) {
        if (typeof e === 'object' && e !== null && 'status' in e && (e as { status: number }).status === 400) {
            deleteConflict.value = {
                hasNotes: (e as { hasNotes: boolean }).hasNotes,
                hasFolders: (e as { hasFolders: boolean }).hasFolders,
            }
            deleteDialogOpen.value = true
        } else {
            emit('close')
        }
    }
}

async function confirmDelete(strategy: 'move_to_root' | 'delete_recursive'): Promise<void> {
    deleteDialogOpen.value = false
    await foldersStore.deleteFolder(props.folderId, strategy)
    emit('close')
}
</script>

<template>
    <div class="context-menu">
        <template v-if="isRenaming">
            <div class="context-menu__rename">
                <input
                    v-model="renameValue"
                    class="context-menu__rename-input"
                    type="text"
                    @keyup.enter="confirmRename"
                    @keyup.escape="isRenaming = false"
                />
                <p v-if="renameError" class="context-menu__error">{{ renameError }}</p>
                <button class="context-menu__item" @click="confirmRename">Сохранить</button>
                <button class="context-menu__item" @click="isRenaming = false">Отмена</button>
            </div>
        </template>
        <template v-else>
            <button class="context-menu__item" @click="startRename">Переименовать</button>
            <button class="context-menu__item" @click="emit('openMoveModal', folderId)">Переместить в…</button>
            <button class="context-menu__item context-menu__item--danger" @click="requestDelete">Удалить</button>
        </template>
    </div>

    <DeleteFolderDialog
        v-if="deleteDialogOpen && deleteConflict"
        :has-notes="deleteConflict.hasNotes"
        :has-folders="deleteConflict.hasFolders"
        @confirm="confirmDelete"
        @cancel="deleteDialogOpen = false"
    />
</template>

<style scoped>
.context-menu {
    position: absolute;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    min-width: 160px;
    z-index: 150;
    padding: 0.25rem 0;
}

.context-menu__item {
    display: block;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: #333;
}

.context-menu__item:hover {
    background: #f5f5f5;
}

.context-menu__item--danger {
    color: #e53e3e;
}

.context-menu__rename {
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.context-menu__rename-input {
    width: 100%;
    padding: 0.35rem 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 0.9rem;
    box-sizing: border-box;
}

.context-menu__error {
    font-size: 0.8rem;
    color: #e53e3e;
    margin: 0;
}
</style>
