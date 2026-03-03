<script setup lang="ts">
import { ref, computed } from 'vue'
import { useFoldersStore } from '@/stores/folders'
import type { FolderTreeNode } from '@/composables/useFolders'

const props = defineProps<{
    sourceFolderId: string
}>()

const emit = defineEmits<{
    confirm: [targetParentId: string | null]
    cancel: []
}>()

const foldersStore = useFoldersStore()
const selected = ref<string | null | '__root__'>('__root__')

function getDescendantIds(nodes: FolderTreeNode[], sourceId: string): Set<string> {
    const ids = new Set<string>()
    function collect(list: FolderTreeNode[]): void {
        for (const n of list) {
            if (n.id === sourceId || ids.has(n.id)) {
                ids.add(n.id)
                for (const c of n.children) {
                    ids.add(c.id)
                    collect([c])
                }
            } else {
                collect(n.children)
            }
        }
    }
    // Get direct descendants of source
    function findDescendants(list: FolderTreeNode[]): void {
        for (const n of list) {
            if (n.id === sourceId) {
                collectChildren(n.children)
                return
            }
            findDescendants(n.children)
        }
    }
    function collectChildren(list: FolderTreeNode[]): void {
        for (const n of list) {
            ids.add(n.id)
            collectChildren(n.children)
        }
    }
    findDescendants(nodes)
    return ids
}

const descendantIds = computed(() => getDescendantIds(foldersStore.tree, props.sourceFolderId))

function flattenTree(nodes: FolderTreeNode[], depth = 0): Array<{ id: string; name: string; depth: number; disabled: boolean }> {
    return nodes.flatMap((node) => {
        const disabled = node.id === props.sourceFolderId || descendantIds.value.has(node.id)
        return [
            { id: node.id, name: node.name, depth, disabled },
            ...flattenTree(node.children, depth + 1),
        ]
    })
}

const flat = computed(() => flattenTree(foldersStore.tree))
</script>

<template>
    <div class="modal-overlay" @click.self="emit('cancel')">
        <div class="modal">
            <h3 class="modal__title">Переместить папку</h3>

            <ul class="modal__list">
                <li
                    class="modal__item"
                    :class="{ 'modal__item--selected': selected === '__root__' }"
                    @click="selected = '__root__'"
                >
                    Верхний уровень
                </li>
                <li
                    v-for="folder in flat"
                    :key="folder.id"
                    class="modal__item"
                    :class="{
                        'modal__item--selected': selected === folder.id,
                        'modal__item--disabled': folder.disabled,
                    }"
                    :style="{ paddingLeft: `${0.75 + folder.depth * 1}rem` }"
                    @click="!folder.disabled && (selected = folder.id)"
                >
                    {{ folder.name }}
                    <span v-if="folder.disabled" class="modal__item-hint">(недоступно)</span>
                </li>
            </ul>

            <div class="modal__actions">
                <button class="btn btn--cancel" @click="emit('cancel')">Отмена</button>
                <button
                    class="btn btn--primary"
                    @click="emit('confirm', selected === '__root__' ? null : selected)"
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
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal__item:hover:not(.modal__item--disabled) {
    background: #f5f5f5;
}

.modal__item--selected {
    background: #e8f0fe;
    color: #1a56db;
}

.modal__item--disabled {
    color: #bbb;
    cursor: not-allowed;
}

.modal__item-hint {
    font-size: 0.75rem;
    color: #bbb;
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
