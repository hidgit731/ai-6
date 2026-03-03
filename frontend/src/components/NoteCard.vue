<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import MoveNoteModal from './MoveNoteModal.vue'
import { useNotesStore } from '@/stores/notes'
import type { NoteListItem } from '@/composables/useNotes'

const props = defineProps<{
    note: NoteListItem
}>()

const router = useRouter()
const notesStore = useNotesStore()
const showMoveModal = ref(false)

function formatDate(isoDate: string): string {
    return new Date(isoDate).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

async function handleMoveConfirm(folderId: string | null): Promise<void> {
    showMoveModal.value = false
    await notesStore.moveNote(props.note.id, folderId)
}
</script>

<template>
    <div class="note-card" @click="router.push({ name: 'note-view', params: { id: props.note.id } })">
        <h2 class="note-title">{{ props.note.title }}</h2>
        <p class="note-date">{{ formatDate(props.note.createdAt) }}</p>
        <p v-if="props.note.preview" class="note-preview">{{ props.note.preview }}</p>
        <div class="note-card__footer">
            <span v-if="props.note.folderId" class="note-folder-badge">📁</span>
            <button
                class="note-card__move-btn"
                title="Переместить в папку"
                @click.stop="showMoveModal = true"
            >
                Переместить
            </button>
        </div>
    </div>

    <MoveNoteModal
        v-if="showMoveModal"
        :current-folder-id="props.note.folderId"
        @confirm="handleMoveConfirm"
        @cancel="showMoveModal = false"
    />
</template>

<style scoped>
.note-card {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: box-shadow 0.15s, border-color 0.15s;
}

.note-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-color: #ccc;
}

.note-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #222;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.note-date {
    font-size: 0.8rem;
    color: #999;
    margin: 0 0 0.5rem;
}

.note-preview {
    font-size: 0.9rem;
    color: #555;
    margin: 0 0 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.note-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 0.25rem;
}

.note-folder-badge {
    font-size: 0.8rem;
    color: #4a90d9;
}

.note-card__move-btn {
    background: none;
    border: none;
    font-size: 0.8rem;
    color: #888;
    cursor: pointer;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
}

.note-card__move-btn:hover {
    background: #f0f0f0;
    color: #4a90d9;
}
</style>
