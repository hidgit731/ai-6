<script setup lang="ts">
import FolderTreeNode from './FolderTreeNode.vue'
import type { FolderTreeNode as FolderTreeNodeType } from '@/composables/useFolders'

const props = defineProps<{
    folder: FolderTreeNodeType
    isExpanded: boolean
    isSelected: boolean
}>()

const emit = defineEmits<{
    select: [id: string]
    toggle: [id: string]
    contextMenu: [id: string, event: MouseEvent]
}>()
</script>

<template>
    <li class="folder-node">
        <div
            class="folder-node__row"
            :class="{ 'folder-node__row--selected': isSelected }"
        >
            <button
                v-if="folder.children.length > 0"
                class="folder-node__toggle"
                @click.stop="emit('toggle', folder.id)"
            >
                {{ isExpanded ? '▾' : '▸' }}
            </button>
            <span v-else class="folder-node__spacer" />

            <span
                class="folder-node__name"
                @click="emit('select', folder.id)"
            >
                {{ folder.name }}
            </span>

            <button
                class="folder-node__menu-btn"
                title="Действия"
                @click.stop="emit('contextMenu', folder.id, $event)"
            >
                …
            </button>
        </div>

        <ul v-if="isExpanded && folder.children.length > 0" class="folder-node__children">
            <FolderTreeNode
                v-for="child in folder.children"
                :key="child.id"
                :folder="child"
                :is-expanded="isExpanded"
                :is-selected="isSelected && child.id === folder.id"
                @select="emit('select', $event)"
                @toggle="emit('toggle', $event)"
                @context-menu="emit('contextMenu', $event, $event as unknown as MouseEvent)"
            />
        </ul>
    </li>
</template>

<style scoped>
.folder-node {
    list-style: none;
    padding: 0;
    margin: 0;
}

.folder-node__row {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.3rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    user-select: none;
}

.folder-node__row:hover {
    background: #f0f0f0;
}

.folder-node__row--selected {
    background: #e8f0fe;
    color: #1a56db;
}

.folder-node__toggle,
.folder-node__menu-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0 0.2rem;
    font-size: 0.8rem;
    color: #666;
    line-height: 1;
}

.folder-node__spacer {
    display: inline-block;
    width: 1.2rem;
}

.folder-node__name {
    flex: 1;
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.folder-node__menu-btn {
    opacity: 0;
    font-size: 1rem;
}

.folder-node__row:hover .folder-node__menu-btn {
    opacity: 1;
}

.folder-node__children {
    padding-left: 1.25rem;
    margin: 0;
}
</style>
