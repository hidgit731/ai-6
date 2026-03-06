<script setup lang="ts">
import { onMounted, watch, computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import NoteCard from '@/components/NoteCard.vue'
import TagCloud from '@/components/TagCloud.vue'
import Pagination from '@/components/Pagination.vue'
import { useNotesStore } from '@/stores/notes'
import { useFoldersStore } from '@/stores/folders'
import { useTagsStore } from '@/stores/tags'

const route = useRoute()
const router = useRouter()
const notesStore = useNotesStore()
const foldersStore = useFoldersStore()
const tagsStore = useTagsStore()

const isTagsOpen = ref(false)

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
    isTagsOpen.value = window.innerWidth >= 768
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
                <NoteCard v-for="note in notesStore.notes" :key="note.id" :note="note" />
            </div>

            <Pagination
                :page="notesStore.pagination.page"
                :total-pages="notesStore.pagination.totalPages"
                @page-change="handlePageChange"
            />
        </template>

        </div>

            <aside v-if="tagsStore.tags.length > 0" class="notes-sidebar">
                <details
                    class="notes-sidebar__accordion"
                    :open="isTagsOpen"
                    @toggle="isTagsOpen = ($event.target as HTMLDetailsElement).open"
                >
                    <summary class="notes-sidebar__toggle">Теги</summary>
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
                </details>
            </aside>
        </div>

    </div>
</template>

<style scoped>
.notes-list-page {
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


.notes-layout {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

.notes-content {
    flex: 1;
    min-width: 0;
    order: 1;
}

.notes-sidebar {
    width: 220px;
    flex-shrink: 0;
    border: 1px solid #e8e8e8;
    border-radius: 6px;
    padding: 0.75rem;
    background: #fafafa;
    order: 2;
}

/* Desktop: hide the <summary> toggle — content is always visible via :open binding */
.notes-sidebar__accordion > summary {
    display: none;
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

/* Mobile: collapse to accordion above notes */
@media (max-width: 767px) {
    .notes-layout {
        flex-direction: column;
    }

    .notes-content {
        order: 2;
        width: 100%;
    }

    .notes-sidebar {
        order: 1;
        width: 100%;
    }

    .notes-sidebar__accordion > summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 600;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.25rem 0;
        list-style: none;
    }

    .notes-sidebar__accordion > summary::after {
        content: '▾';
        font-size: 0.8rem;
    }

    .notes-sidebar__accordion[open] > summary::after {
        content: '▴';
    }
}
</style>
