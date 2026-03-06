<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import MarkdownEditor from '@/components/MarkdownEditor.vue'
import TagInput from '@/components/TagInput.vue'
import VersionHistoryPanel from '@/components/VersionHistoryPanel.vue'
import { useNotesStore } from '@/stores/notes'
import { useNoteVersionsStore } from '@/stores/noteVersions'
import { useNoteLinksStore } from '@/stores/noteLinks'
import NoteLinksPanel from '@/components/NoteLinksPanel.vue'
const route = useRoute()
const router = useRouter()
const notesStore = useNotesStore()
const noteVersionsStore = useNoteVersionsStore()
const noteLinksStore = useNoteLinksStore()

const isEditMode = computed(() => route.name === 'note-edit')
const noteId = computed(() => route.params.id as string | undefined)

const title = ref('')
const content = ref('')
const tags = ref<string[]>([])
const titleError = ref<string | null>(null)
const submitError = ref<string | null>(null)
const isLoading = ref(false)

const wikiLinkMap = computed<Map<string, string>>(() => {
    const links = noteLinksStore.currentNoteLinks?.outgoing ?? []
    return new Map(links.map((r) => [r.title, r.id]))
})

onMounted(async () => {
    if (isEditMode.value && noteId.value) {
        await notesStore.fetchById(noteId.value)
        if (notesStore.currentNote) {
            title.value = notesStore.currentNote.title
            content.value = notesStore.currentNote.content ?? ''
            tags.value = notesStore.currentNote.tags.map((t) => t.name)
            await noteLinksStore.fetchLinks(noteId.value)
        }
    }
})

onUnmounted(() => {
    noteLinksStore.clearLinks()
})

// Sync editor when store.currentNote is updated after a revert
watch(
    () => notesStore.currentNote,
    (note) => {
        if (note) {
            title.value = note.title
            content.value = note.content ?? ''
            tags.value = note.tags.map((t) => t.name)
        }
    },
)

function handleMarkdownClick(e: MouseEvent): void {
    const a = (e.target as Element).closest('a.wiki-link')
    if (!a) return
    e.preventDefault()
    const id = a.getAttribute('data-note-id')
    if (id) {
        router.push(`/notes/${id}`)
    }
}

async function handleSave(): Promise<void> {
    titleError.value = null
    submitError.value = null

    if (!title.value.trim()) {
        titleError.value = 'Заголовок не может быть пустым.'
        return
    }

    isLoading.value = true
    try {
        const payload = {
            title: title.value.trim(),
            content: content.value || null,
            tags: tags.value,
        }

        let note
        if (isEditMode.value && noteId.value) {
            note = await notesStore.updateNote(noteId.value, payload)
        } else {
            note = await notesStore.createNote(payload)
        }

        await router.push({ name: 'note-view', params: { id: note.id } })
    } catch (e: unknown) {
        if (e && typeof e === 'object' && 'status' in e && (e as { status: number }).status === 422) {
            const err = e as { errors?: { title?: string } }
            titleError.value = err.errors?.title ?? null
        } else {
            submitError.value = e instanceof Error ? e.message : 'Ошибка сохранения'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <div class="note-edit-page">
        <div class="page-header">
            <button class="btn-back" @click="router.back()">← Назад</button>
            <h1 class="page-title">{{ isEditMode ? 'Редактирование заметки' : 'Новая заметка' }}</h1>
            <button
                v-if="isEditMode && noteId"
                class="btn-history"
                type="button"
                @click="noteVersionsStore.openPanel(noteId!)"
            >
                History
            </button>
        </div>

        <div v-if="notesStore.loading && isEditMode" class="loading">Загрузка...</div>
        <div v-else-if="notesStore.error" class="error-message">{{ notesStore.error }}</div>
        <template v-else>
            <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events -->
            <div @click="handleMarkdownClick">
                <MarkdownEditor
                    v-model:title="title"
                    v-model:content="content"
                    :wiki-link-map="wikiLinkMap"
                />
            </div>

            <TagInput v-model="tags" placeholder="Добавить тег..." />

            <div v-if="titleError" class="field-error">{{ titleError }}</div>
            <div v-if="submitError" class="error-message">{{ submitError }}</div>

            <div class="actions">
                <button
                    class="btn-save"
                    :disabled="isLoading"
                    @click="handleSave"
                >
                    {{ isLoading ? 'Сохранение...' : 'Сохранить' }}
                </button>
            </div>
        </template>

        <NoteLinksPanel v-if="isEditMode && noteId" :note-id="noteId!" />

        <VersionHistoryPanel
            v-if="noteVersionsStore.isPanelOpen && noteId"
            :note-id="noteId"
            :current-title="title"
            :current-content="content || null"
        />
    </div>
</template>

<style scoped>
.note-edit-page {
    display: flex;
    flex-direction: column;
    height: 100vh;
    padding: 1rem;
    box-sizing: border-box;
    gap: 0.75rem;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn-history {
    margin-left: auto;
    background: none;
    border: 1px solid #4a90d9;
    color: #4a90d9;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-history:hover {
    background: #f0f7ff;
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

.page-title {
    font-size: 1.2rem;
    margin: 0;
    color: #333;
}

.loading,
.error-message {
    padding: 0.75rem;
    border-radius: 4px;
    text-align: center;
}

.error-message {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.field-error {
    color: #c33;
    font-size: 0.9rem;
    margin-top: -0.5rem;
}

.actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.5rem;
}

.btn-save {
    background: #4a90d9;
    color: white;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 4px;
    font-size: 1rem;
    cursor: pointer;
}

.btn-save:hover:not(:disabled) {
    background: #357abd;
}

.btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}


</style>
