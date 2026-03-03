<script setup lang="ts">
import { ref } from 'vue'
import { useFoldersStore } from '@/stores/folders'
import type { FolderTreeNode } from '@/composables/useFolders'

defineProps<{
    currentFolderId?: string | null
}>()

const emit = defineEmits<{
    confirm: [folderId: string | null]
    cancel: []
}>()

const foldersStore = useFoldersStore()
const selected = ref<string | null | 'none'>('none')

function flattenTree(nodes: FolderTreeNode[], depth = 0): Array<{ id: string; name: string; depth: number }> {
    return nodes.flatMap((node) => [
        { id: node.id, name: node.name, depth },
        ...flattenTree(node.children, depth + 1),
    ])
}

const flat = flattenTree(foldersStore.tree)
</script>

<template>
    <div class="modal-overlay" @click.self="emit('cancel')">
        <div class="modal">
            <h3 class="modal__title">Переместить заметку</h3>

            <ul class="modal__list">
                <li
                    class="modal__item"
                    :class="{ 'modal__item--selected': selected === null }"
                    @click="selected = null"
                >
                    Без папки
                </li>
                <li
                    v-for="folder in flat"
                    :key="folder.id"
                    class="modal__item"
                    :class="{ 'modal__item--selected': selected === folder.id }"
                    :style="{ paddingLeft: `${0.75 + folder.depth * 1}rem` }"
                    @click="selected = folder.id"
                >
                    {{ folder.name }}
                </li>
            </ul>

            <div class="modal__actions">
                <button class="btn btn--cancel" @click="emit('cancel')">Отмена</button>
                <button
                    class="btn btn--primary"
                    @click="emit('confirm', selected === 'none' ? null : selected)"
                >
                    Переместить
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
}

.modal {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    width: 380px;
    max-width: 90vw;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.modal__title {
    margin: 0 0 1rem;
    font-size: 1.1rem;
}

.modal__list {
    list-style: none;
    margin: 0 0 1rem;
    padding: 0;
    overflow-y: auto;
    flex: 1;
    border: 1px solid #eee;
    border-radius: 4px;
}

.modal__item {
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    font-size: 0.9rem;
    color: #333;
}

.modal__item:hover {
    background: #f5f5f5;
}

.modal__item--selected {
    background: #e8f0fe;
    color: #1a56db;
}

.modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.btn {
    padding: 0.45rem 1rem;
    border-radius: 4px;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn--cancel {
    background: #f5f5f5;
    border-color: #ddd;
    color: #333;
}

.btn--primary {
    background: #4a90d9;
    color: #fff;
}

.btn--primary:hover {
    background: #357abd;
}
</style>
