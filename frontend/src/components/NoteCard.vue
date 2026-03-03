<script setup lang="ts">
import { useRouter } from 'vue-router'
import type { NoteListItem } from '@/composables/useNotes'

const props = defineProps<{
    note: NoteListItem
}>()

const router = useRouter()

function formatDate(isoDate: string): string {
    return new Date(isoDate).toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}
</script>

<template>
    <div class="note-card" @click="router.push({ name: 'note-view', params: { id: props.note.id } })">
        <h2 class="note-title">{{ props.note.title }}</h2>
        <p class="note-date">{{ formatDate(props.note.createdAt) }}</p>
        <p v-if="props.note.preview" class="note-preview">{{ props.note.preview }}</p>
    </div>
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
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
</style>
