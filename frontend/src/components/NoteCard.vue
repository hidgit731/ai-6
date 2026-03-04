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

async function handleToggleFavorite(event: Event): Promise<void> {
    event.stopPropagation()
    await notesStore.toggleFavorite(props.note.id)
}

async function handleDeleteNote(event: Event): Promise<void> {
    event.stopPropagation()
    await notesStore.softDeleteNote(props.note.id)
}
</script>

<template>
    <div class="note-card" @click="router.push({ name: 'note-view', params: { id: props.note.id } })">
        <h2 class="note-title">{{ props.note.title }}</h2>
        <p class="note-date">{{ formatDate(props.note.createdAt) }}</p>
        <p v-if="props.note.preview" class="note-preview">{{ props.note.preview }}</p>
        <div v-if="props.note.tags && props.note.tags.length > 0" class="note-tags">
            <span v-for="tag in props.note.tags" :key="tag.id" class="note-tag-badge">{{ tag.name }}</span>
        </div>
        <div class="note-card__footer">
            <div class="note-card__left">
                <span v-if="props.note.folderId" class="note-folder-badge">📁</span>
            </div>
            <div class="note-card__actions">
                <button
                    class="note-card__btn"
                    :title="props.note.isFavorite ? 'Убрать из избранного' : 'Добавить в избранное'"
                    @click="handleToggleFavorite"
                >
                    {{ props.note.isFavorite ? '★' : '☆' }}
                </button>
                <button
                    class="note-card__btn"
                    title="Удалить"
                    @click="handleDeleteNote"
                >
                    🗑️
                </button>
                <button
                    class="note-card__move-btn"
                    title="Переместить в папку"
                    @click.stop="showMoveModal = true"
                >
                    Переместить
                </button>
            </div>
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
    margin-top: 0.75rem;
    gap: 0.5rem;
}

.note-card__left {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.note-folder-badge {
    font-size: 0.8rem;
    color: #4a90d9;
}

.note-card__actions {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.note-card__btn {
    background: none;
    border: none;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 0.2rem 0.4rem;
    border-radius: 3px;
    transition: background 0.15s;
}

.note-card__btn:hover {
    background: #f0f0f0;
}

.note-card__move-btn {
    background: none;
    border: none;
    font-size: 0.8rem;
    color: #888;
    cursor: pointer;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    transition: background 0.15s;
}

.note-card__move-btn:hover {
    background: #f0f0f0;
    color: #4a90d9;
}

.note-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin-bottom: 0.4rem;
}

.note-tag-badge {
    background: #e8f0fb;
    color: #4a90d9;
    border-radius: 3px;
    padding: 0.1rem 0.4rem;
    font-size: 0.75rem;
}
</style>
