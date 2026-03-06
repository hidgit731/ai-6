<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useNotesStore } from '@/stores/notes'
import type { NoteListItem } from '@/composables/useNotes'

const notesStore = useNotesStore()
const bulkDeleteError = ref<string | null>(null)

async function handleEmptyTrash(): Promise<void> {
    if (!confirm('Все заметки из корзины будут удалены безвозвратно. Продолжить?')) return
    bulkDeleteError.value = null
    try {
        await notesStore.emptyAllTrash()
    } catch (e) {
        bulkDeleteError.value = e instanceof Error ? e.message : 'Ошибка очистки корзины'
    }
}

onMounted(async () => {
    await notesStore.fetchTrash(1)
})

async function onPrevious(): Promise<void> {
    if (notesStore.trashPage > 1) {
        await notesStore.fetchTrash(notesStore.trashPage - 1)
    }
}

async function onNext(): Promise<void> {
    const maxPages = Math.ceil(notesStore.trashTotal / 20)
    if (notesStore.trashPage < maxPages) {
        await notesStore.fetchTrash(notesStore.trashPage + 1)
    }
}

async function handleRestore(note: NoteListItem): Promise<void> {
    try {
        await notesStore.restoreNote(note.id)
    } catch (e) {
        console.error('Failed to restore note:', e)
    }
}

async function handleDeletePermanently(note: NoteListItem): Promise<void> {
    if (confirm('Это будет удалено безвозвратно. Продолжить?')) {
        try {
            await notesStore.permanentDeleteNote(note.id)
        } catch (e) {
            console.error('Failed to delete note permanently:', e)
        }
    }
}

function formatDate(isoDate: string | undefined): string {
    if (!isoDate) return ''
    const date = new Date(isoDate)
    return date.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <div class="trash-page">
        <div class="trash-page__header">
            <h1>Корзина</h1>
            <button
                v-if="notesStore.trashTotal > 0"
                class="trash-item__btn trash-item__btn--delete btn-empty-trash"
                :disabled="notesStore.isEmptyingTrash"
                @click="handleEmptyTrash"
            >
                {{ notesStore.isEmptyingTrash ? 'Удаление...' : 'Удалить все записи навсегда' }}
            </button>
        </div>
        <p v-if="notesStore.error" class="error-message">{{ notesStore.error }}</p>
        <p v-if="bulkDeleteError" class="error-message">{{ bulkDeleteError }}</p>

        <div v-if="notesStore.trash.length === 0" class="empty-state">
            <p>Корзина пуста</p>
        </div>

        <div v-else class="trash-list">
            <div v-for="note in notesStore.trash" :key="note.id" class="trash-item">
                <div class="trash-item__info">
                    <h3 class="trash-item__title">{{ note.title }}</h3>
                    <p v-if="note.deletedAt" class="trash-item__date">
                        Удалена: {{ formatDate(note.deletedAt) }}
                    </p>
                </div>
                <div class="trash-item__actions">
                    <button class="trash-item__btn trash-item__btn--restore" @click="handleRestore(note)">
                        ↩️ Восстановить
                    </button>
                    <button
                        class="trash-item__btn trash-item__btn--delete"
                        @click="handleDeletePermanently(note)"
                    >
                        🗑️ Удалить навсегда
                    </button>
                </div>
            </div>
        </div>

        <div v-if="notesStore.trashTotal > 0" class="pagination">
            <button :disabled="notesStore.trashPage <= 1" @click="onPrevious">
                ← Предыдущая
            </button>
            <span class="pagination-info">
                Страница {{ notesStore.trashPage }} из {{ Math.ceil(notesStore.trashTotal / 20) }}
            </span>
            <button
                :disabled="notesStore.trashPage >= Math.ceil(notesStore.trashTotal / 20)"
                @click="onNext"
            >
                Следующая →
            </button>
        </div>
    </div>
</template>

<style scoped>
.trash-page {
    padding: 2rem;
}

.trash-page__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 2rem;
}

.trash-page__header h1 {
    margin: 0;
    color: #222;
    font-size: 1.5rem;
}

.btn-empty-trash {
    white-space: nowrap;
}

.btn-empty-trash:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.error-message {
    background: #fee;
    border: 1px solid #fcc;
    color: #c33;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #999;
    font-size: 1.1rem;
}

.trash-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.trash-item {
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.trash-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-color: #ccc;
}

.trash-item__info {
    flex: 1;
}

.trash-item__title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 0.25rem;
    color: #222;
}

.trash-item__date {
    font-size: 0.8rem;
    color: #999;
    margin: 0;
}

.trash-item__actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.trash-item__btn {
    background: none;
    border: 1px solid #ddd;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: all 0.15s;
    white-space: nowrap;
}

.trash-item__btn--restore {
    color: #4a90d9;
    border-color: #4a90d9;
}

.trash-item__btn--restore:hover {
    background: #e8f0fb;
}

.trash-item__btn--delete {
    color: #d94a4a;
    border-color: #d94a4a;
}

.trash-item__btn--delete:hover {
    background: #fbe8e8;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
}

.pagination-info {
    font-size: 0.9rem;
    color: #666;
}

.pagination button {
    background: #4a90d9;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.15s;
}

.pagination button:hover:not(:disabled) {
    background: #357abd;
}

.pagination button:disabled {
    background: #ccc;
    cursor: not-allowed;
}
</style>
