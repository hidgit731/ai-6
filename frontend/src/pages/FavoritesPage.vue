<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotesStore } from '@/stores/notes'
import NoteCard from '@/components/NoteCard.vue'

const notesStore = useNotesStore()

onMounted(async () => {
    await notesStore.fetchFavorites(1)
})

async function onPrevious(): Promise<void> {
    if (notesStore.favoritesPage > 1) {
        await notesStore.fetchFavorites(notesStore.favoritesPage - 1)
    }
}

async function onNext(): Promise<void> {
    const maxPages = Math.ceil(notesStore.favoritesTotal / 20)
    if (notesStore.favoritesPage < maxPages) {
        await notesStore.fetchFavorites(notesStore.favoritesPage + 1)
    }
}
</script>

<template>
    <div class="favorites-page">
        <h1>Избранное</h1>
        <p v-if="notesStore.error" class="error-message">{{ notesStore.error }}</p>

        <div v-if="notesStore.favorites.length === 0" class="empty-state">
            <p>Нет избранных заметок</p>
        </div>

        <div v-else class="notes-list">
            <NoteCard
                v-for="note in notesStore.favorites"
                :key="note.id"
                :note="note"
            />
        </div>

        <div v-if="notesStore.favoritesTotal > 0" class="pagination">
            <button
                :disabled="notesStore.favoritesPage <= 1"
                @click="onPrevious"
            >
                ← Предыдущая
            </button>
            <span class="pagination-info">
                Страница {{ notesStore.favoritesPage }} из
                {{ Math.ceil(notesStore.favoritesTotal / 20) }}
            </span>
            <button
                :disabled="notesStore.favoritesPage >= Math.ceil(notesStore.favoritesTotal / 20)"
                @click="onNext"
            >
                Следующая →
            </button>
        </div>
    </div>
</template>

<style scoped>
.favorites-page {
    padding: 2rem;
}

h1 {
    margin: 0 0 2rem 0;
    color: #222;
    font-size: 1.5rem;
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

.notes-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
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
