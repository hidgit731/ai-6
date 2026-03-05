<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useNoteLinksStore } from '@/stores/noteLinks'

defineProps<{
    noteId: string
}>()

const router = useRouter()
const noteLinksStore = useNoteLinksStore()

const incoming = computed(() => noteLinksStore.currentNoteLinks?.incoming ?? [])
const outgoing = computed(() => noteLinksStore.currentNoteLinks?.outgoing ?? [])
const hasLinks = computed(() => incoming.value.length > 0 || outgoing.value.length > 0)

function navigate(id: string): void {
    router.push(`/notes/${id}`)
}
</script>

<template>
    <details v-if="hasLinks" open class="note-links-panel">
        <summary class="panel-summary">Связанные заметки</summary>

        <div class="links-section">
            <h4 class="section-title">Входящие ({{ incoming.length }})</h4>
            <ul v-if="incoming.length > 0" class="links-list">
                <li v-for="link in incoming" :key="link.id">
                    <a class="link-item" @click.prevent="navigate(link.id)">{{ link.title }}</a>
                </li>
            </ul>
            <p v-else class="empty-links">Нет входящих ссылок</p>
        </div>

        <div class="links-section">
            <h4 class="section-title">Исходящие ({{ outgoing.length }})</h4>
            <ul v-if="outgoing.length > 0" class="links-list">
                <li v-for="link in outgoing" :key="link.id">
                    <a class="link-item" @click.prevent="navigate(link.id)">{{ link.title }}</a>
                </li>
            </ul>
            <p v-else class="empty-links">Нет исходящих ссылок</p>
        </div>
    </details>
</template>

<style scoped>
.note-links-panel {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}

.panel-summary {
    background: #f8f8f8;
    padding: 0.6rem 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #444;
    cursor: pointer;
    border-bottom: 1px solid #e0e0e0;
    user-select: none;
}

.panel-summary:hover {
    background: #f0f0f0;
}

.links-section {
    padding: 0.75rem 1rem;
}

.links-section + .links-section {
    border-top: 1px solid #f0f0f0;
}

.section-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: #666;
    margin: 0 0 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.links-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.link-item {
    color: #4a90d9;
    text-decoration: none;
    font-size: 0.9rem;
    cursor: pointer;
}

.link-item:hover {
    text-decoration: underline;
}

.empty-links {
    font-size: 0.85rem;
    color: #aaa;
    margin: 0;
    font-style: italic;
}
</style>
