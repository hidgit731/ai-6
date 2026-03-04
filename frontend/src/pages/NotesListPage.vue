<script setup lang="ts">
import { onMounted, watch, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NoteCard from '@/components/NoteCard.vue'
import TagCloud from '@/components/TagCloud.vue'
import Pagination from '@/components/Pagination.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'

const route = useRoute()
const router = useRouter()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()

const deleteTarget = ref<string | null>(null)

const pageTitle = computed(() => {
    const fid = foldersStore.selectedFolderId
    if (fid === null) return 'Заметки'
    if (fid === 'none') return 'Без папки'
    const node = findInTree(foldersStore.tree, fid)
    return node ? node.name : 'Заметки'
})

function findInTree(nodes: typeof foldersStore.tree, id: string): { name: string } | null {
    for (const n of nodes) {
        if (n.id === id) return n
        const found = findInTree(n.children, id)
        if (found) return found
    }
    return null
}

function currentPage(): number {
    const p = Number(route.query.page)
    return p > 0 ? p : 1
}

async function loadPage(page: number): Promise<void> {
    await notesStore.fetchList(page)
}

onMounted(() => {
    tagsStore.initFromUrl(route.query.tags as string | string[] | undefined)
    tagsStore.fetchCloud()
    loadPage(currentPage())
})

watch(() => route.query.page, () => loadPage(currentPage()))

watch(
    () => tagsStore.selectedTagNames,
    (names) => {
        const query = { ...route.query }
        if (names.length > 0) {
            query.tags = names
        } else {
            delete query.tags
        }
        router.push({ name: 'notes-list', query })
    },
)

watch(
    () => route.query.tags,
    (newTags) => {
        tagsStore.initFromUrl(newTags as string | string[] | undefined)
    },
)

function handlePageChange(page: number): void {
    router.push({ name: 'notes-list', query: { page } })
}

async function handleCreate(): Promise<void> {
    const fid = foldersStore.selectedFolderId
    router.push({
        name: 'note-create',
        query: fid ? { folderId: String(fid) } : {},
    })
}

async function confirmDelete(): Promise<void> {
    if (!deleteTarget.value) return
    const id = deleteTarget.value
    deleteTarget.value = null
    await notesStore.deleteNote(id)

    const pg = currentPage()
    const remaining = notesStore.notes.filter(n => n.id !== id).length
    const newPage = remaining === 0 && pg > 1 ? pg - 1 : pg
    await loadPage(newPage)
    if (newPage !== pg) {
        router.replace({ name: 'notes-list', query: newPage > 1 ? { page: newPage } : {} })
    }
}
</script>

<template>
    <div class="notes-list-page">
        <div class="page-header">
            <h1 class="page-title">{{ pageTitle }}</h1>
            <button class="btn-create" @click="handleCreate">
                + Новая заметка
            </button>
        </div>

        <div class="notes-layout">
            <aside class="notes-sidebar">
                <h3 class="sidebar-title">Теги</h3>
                <TagCloud
                    :tags="tagsStore.tags"
                    :selected-names="tagsStore.selectedTagNames"
                    @tag-click="(name) => tagsStore.selectedTagNames.includes(name) ? tagsStore.deselectTag(name) : tagsStore.selectTag(name)"
                />
                <button
                    v-if="tagsStore.selectedTagNames.length > 0"
                    class="btn-clear-tags"
                    @click="tagsStore.clearTags()"
                >
                    Сбросить фильтр
                </button>
            </aside>

            <div class="notes-content">

        <div v-if="notesStore.loading" class="loading">Загрузка...</div>

        <div v-else-if="notesStore.error" class="error-message">
            {{ notesStore.error }}
        </div>

        <template v-else>
            <div v-if="notesStore.notes.length === 0" class="empty-state">
                <p v-if="foldersStore.selectedFolderId !== null">
                    В этой папке пока нет заметок.
                </p>
                <p v-else>Заметок пока нет.</p>
                <button class="btn-create" @click="handleCreate">
                    Создать первую заметку
                </button>
            </div>

            <div v-else class="notes-grid">
                <div v-for="note in notesStore.notes" :key="note.id" class="note-row">
                    <NoteCard :note="note" class="note-card-grow" />
                    <button
                        class="btn-delete-note"
                        title="Удалить заметку"
                        @click.stop="deleteTarget = note.id"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <Pagination
                :page="notesStore.pagination.page"
                :total-pages="notesStore.pagination.totalPages"
                @page-change="handlePageChange"
            />
        </template>

        </div>
        </div>

        <ConfirmDialog
            v-if="deleteTarget"
            title="Удалить заметку?"
            message="Это действие необратимо. Заметка будет удалена навсегда."
            confirm-label="Удалить"
            @confirm="confirmDelete"
            @cancel="deleteTarget = null"
        />
    </div>
</template>

<style scoped>
.notes-list-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 1.5rem 1rem;
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.page-title {
    font-size: 1.75rem;
    margin: 0;
    color: #222;
}

.btn-create {
    background: #4a90d9;
    color: white;
    border: none;
    padding: 0.55rem 1.2rem;
    border-radius: 4px;
    font-size: 0.95rem;
    cursor: pointer;
}

.btn-create:hover {
    background: #357abd;
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

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #888;
}

.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.notes-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.note-row {
    display: flex;
    align-items: stretch;
    gap: 0.5rem;
}

.note-card-grow {
    flex: 1;
}

.btn-delete-note {
    background: none;
    border: 1px solid #ddd;
    color: #999;
    border-radius: 4px;
    padding: 0.4rem 0.6rem;
    cursor: pointer;
    align-self: center;
    line-height: 1;
    font-size: 0.85rem;
    transition: background 0.1s, color 0.1s, border-color 0.1s;
}

.btn-delete-note:hover {
    background: #fee;
    color: #c33;
    border-color: #fcc;
}

.notes-layout {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

.notes-sidebar {
    width: 200px;
    flex-shrink: 0;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 0.75rem;
    background: #fafafa;
}

.sidebar-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #555;
    margin: 0 0 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.notes-content {
    flex: 1;
    min-width: 0;
}

.btn-clear-tags {
    margin-top: 0.5rem;
    background: none;
    border: 1px solid #ddd;
    color: #888;
    font-size: 0.8rem;
    border-radius: 3px;
    padding: 0.2rem 0.5rem;
    cursor: pointer;
    width: 100%;
}

.btn-clear-tags:hover {
    background: #f0f0f0;
}
</style>
