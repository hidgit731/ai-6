<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MarkdownPreview from '@/components/MarkdownPreview.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import NoteLinksPanel from '@/components/NoteLinksPanel.vue'
import { useNotesStore } from '@/stores/notes'
import { useNoteLinksStore } from '@/stores/noteLinks'
import { useExport } from '@/composables/useExport'

const route = useRoute()
const router = useRouter()
const notesStore = useNotesStore()
const noteLinksStore = useNoteLinksStore()
const { downloadMarkdown, downloadPdf, isPdfLoading, pdfError } = useExport()

const showDeleteDialog = ref(false)
const deleteError = ref<string | null>(null)

const wikiLinkMap = computed<Map<string, string>>(() => {
    const links = noteLinksStore.currentNoteLinks?.outgoing ?? []
    return new Map(links.map((r) => [r.title, r.id]))
})

onMounted(async () => {
    const id = route.params.id as string
    await notesStore.fetchById(id)
    await noteLinksStore.fetchLinks(id)
})

onUnmounted(() => {
    noteLinksStore.clearLinks()
})

function handleMarkdownClick(e: MouseEvent): void {
    const a = (e.target as Element).closest('a.wiki-link')
    if (!a) return
    e.preventDefault()
    const id = a.getAttribute('data-note-id')
    if (id) {
        router.push(`/notes/${id}`)
    }
}

function formatDate(isoDate: string): string {
    return new Date(isoDate).toLocaleString('ru-RU', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

async function handleDelete(): Promise<void> {
    showDeleteDialog.value = false
    const id = route.params.id as string
    try {
        await notesStore.deleteNote(id)
        await router.push({ name: 'notes-list' })
    } catch (e) {
        deleteError.value = e instanceof Error ? e.message : 'Ошибка удаления'
    }
}
</script>

<template>
    <div class="note-view-page">
        <div v-if="notesStore.loading" class="loading">Загрузка...</div>

        <div v-else-if="notesStore.error" class="error-message">
            {{ notesStore.error }}
            <div class="mt-1">
                <button class="btn-back" @click="router.push({ name: 'notes-list' })">
                    ← К списку заметок
                </button>
            </div>
        </div>

        <template v-else-if="notesStore.currentNote">
            <div class="page-header">
                <button class="btn-back" @click="router.push({ name: 'notes-list' })">
                    ← Назад
                </button>
                <div class="header-actions">
                    <button
                        class="btn-export"
                        type="button"
                        @click="downloadMarkdown(notesStore.currentNote!.id)"
                    >
                        Скачать .md
                    </button>
                    <button
                        class="btn-export btn-export--pdf"
                        type="button"
                        :disabled="isPdfLoading"
                        @click="downloadPdf(notesStore.currentNote!.id)"
                    >
                        {{ isPdfLoading ? 'Генерация...' : 'Скачать PDF' }}
                    </button>
                    <span v-if="pdfError" class="export-error">{{ pdfError }}</span>
                    <button
                        class="btn-edit"
                        @click="router.push({ name: 'note-edit', params: { id: notesStore.currentNote!.id } })"
                    >
                        Редактировать
                    </button>
                    <button class="btn-delete" @click="showDeleteDialog = true">
                        Удалить
                    </button>
                </div>
            </div>

            <h1 class="note-title">{{ notesStore.currentNote.title }}</h1>
            <p class="note-meta">
                Создано: {{ formatDate(notesStore.currentNote.createdAt) }}
                <span v-if="notesStore.currentNote.updatedAt !== notesStore.currentNote.createdAt">
                    · Обновлено: {{ formatDate(notesStore.currentNote.updatedAt) }}
                </span>
            </p>

            <div v-if="deleteError" class="error-message">{{ deleteError }}</div>

            <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
            <div class="note-content" @click="handleMarkdownClick">
                <MarkdownPreview
                    v-if="notesStore.currentNote.content"
                    :content="notesStore.currentNote.content"
                    :wiki-link-map="wikiLinkMap"
                />
                <p v-else class="empty-content">Содержимое отсутствует.</p>
            </div>

            <NoteLinksPanel :note-id="notesStore.currentNote.id" />
        </template>

        <ConfirmDialog
            v-if="showDeleteDialog"
            title="Удалить заметку?"
            message="Это действие необратимо. Заметка будет удалена навсегда."
            confirm-label="Удалить"
            @confirm="handleDelete"
            @cancel="showDeleteDialog = false"
        />
    </div>
</template>

<style scoped>
.note-view-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.loading {
    text-align: center;
    color: #888;
    padding: 2rem;
}

.error-message {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
    padding: 0.75rem;
    border-radius: 4px;
}

.mt-1 {
    margin-top: 0.75rem;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.btn-back {
    background: none;
    border: 1px solid #ddd;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    color: #555;
}

.btn-back:hover {
    background: #f5f5f5;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-export {
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.45rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-export:hover:not(:disabled) {
    background: #5a6268;
}

.btn-export--pdf {
    background: #dc3545;
}

.btn-export--pdf:hover:not(:disabled) {
    background: #c82333;
}

.btn-export:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.export-error {
    color: #dc3545;
    font-size: 0.8rem;
    align-self: center;
}

.btn-edit {
    background: #4a90d9;
    color: white;
    border: none;
    padding: 0.45rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}

.btn-edit:hover {
    background: #357abd;
}

.btn-delete {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.45rem 1rem;
    border-radius: 4px;
    cursor: pointer;
}

.btn-delete:hover {
    background: #c0392b;
}

.note-title {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #222;
}

.note-meta {
    font-size: 0.85rem;
    color: #999;
    margin: 0;
}

.note-content {
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    overflow: hidden;
    min-height: 200px;
}

.empty-content {
    color: #aaa;
    padding: 1.5rem;
    text-align: center;
}
</style>
