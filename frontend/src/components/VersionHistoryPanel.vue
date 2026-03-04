<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useNoteVersionsStore } from '@/stores/noteVersions'
import VersionDiffView from '@/components/VersionDiffView.vue'
import type { NoteVersion } from '@/composables/useNoteVersions'

const props = defineProps<{
    noteId: string
    currentTitle: string
    currentContent: string | null
}>()

const store = useNoteVersionsStore()
const sentinel = ref<HTMLElement | null>(null)
const showConfirmDialog = ref(false)
const pendingRevertVersion = ref<NoteVersion | null>(null)

let observer: IntersectionObserver | null = null

function setupObserver(): void {
    if (observer) {
        observer.disconnect()
    }
    if (!sentinel.value) return

    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting && store.hasMore && !store.loading) {
                store.loadMore(props.noteId)
            }
        },
        { threshold: 0.1 },
    )
    observer.observe(sentinel.value)
}

onMounted(() => {
    setupObserver()
})

onUnmounted(() => {
    observer?.disconnect()
})

watch(
    () => store.versions.length,
    () => {
        if (sentinel.value) {
            setupObserver()
        }
    },
)

function handleVersionClick(version: NoteVersion): void {
    store.selectVersion(props.noteId, version)
}

function handleRevertClick(version: NoteVersion): void {
    pendingRevertVersion.value = version
    showConfirmDialog.value = true
}

async function confirmRevert(): Promise<void> {
    if (!pendingRevertVersion.value) return
    showConfirmDialog.value = false
    await store.revert(props.noteId, pendingRevertVersion.value.id)
    pendingRevertVersion.value = null
}

function cancelRevert(): void {
    showConfirmDialog.value = false
    pendingRevertVersion.value = null
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString()
}
</script>

<template>
    <aside class="version-panel">
        <header class="version-panel__header">
            <h3 class="version-panel__title">Version History</h3>
            <button class="version-panel__close" type="button" @click="store.closePanel()">
                ✕
            </button>
        </header>

        <div class="version-panel__body">
            <p
                v-if="!store.loading && store.versions.length === 0"
                class="version-panel__empty"
            >
                No versions yet. Edit the note to create a version.
            </p>

            <ul v-else class="version-list">
                <li
                    v-for="version in store.versions"
                    :key="version.id"
                    class="version-list__item"
                    :class="{ 'version-list__item--selected': store.selectedVersion?.id === version.id }"
                    @click="handleVersionClick(version)"
                >
                    <span class="version-list__number">v{{ version.versionNumber }}</span>
                    <span class="version-list__date">{{ formatDate(version.createdAt) }}</span>
                    <span class="version-list__title">{{ version.title }}</span>
                </li>
            </ul>

            <div v-if="store.loading" class="version-panel__loading">Loading…</div>

            <div ref="sentinel" class="version-panel__sentinel" />

            <VersionDiffView
                v-if="store.selectedVersion"
                :current-title="currentTitle"
                :current-content="currentContent"
                :selected-version="store.selectedVersion"
            />

            <div v-if="store.selectedVersion" class="version-panel__actions">
                <button
                    class="version-panel__revert-btn"
                    type="button"
                    :disabled="store.loading"
                    @click="handleRevertClick(store.selectedVersion!)"
                >
                    Revert to v{{ store.selectedVersion.versionNumber }}
                </button>
            </div>

            <p v-if="store.error" class="version-panel__error">{{ store.error }}</p>
        </div>

        <div v-if="showConfirmDialog" class="confirm-overlay">
            <div class="confirm-dialog" role="dialog" aria-modal="true">
                <p>
                    Revert to version #{{ pendingRevertVersion?.versionNumber }}?
                    Current content will be saved as a new version.
                </p>
                <div class="confirm-dialog__actions">
                    <button type="button" class="btn btn--primary" @click="confirmRevert">
                        Confirm
                    </button>
                    <button type="button" class="btn" @click="cancelRevert">Cancel</button>
                </div>
            </div>
        </div>
    </aside>
</template>

<style lang="scss" scoped>
.version-panel {
    position: fixed;
    right: 0;
    top: 0;
    height: 100vh;
    width: 380px;
    background: #fff;
    border-left: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
    z-index: 100;
    box-shadow: -2px 0 8px rgba(0, 0, 0, 0.1);

    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #e0e0e0;
    }

    &__title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
    }

    &__close {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0.25rem;
        color: #666;

        &:hover {
            color: #333;
        }
    }

    &__body {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem 0;
    }

    &__empty {
        padding: 1rem;
        color: #888;
        text-align: center;
        font-size: 0.9rem;
    }

    &__loading {
        padding: 0.5rem 1rem;
        color: #888;
        font-size: 0.85rem;
    }

    &__sentinel {
        height: 1px;
    }

    &__actions {
        padding: 0.5rem 1rem;
    }

    &__revert-btn {
        width: 100%;
        padding: 0.5rem;
        background: #e74c3c;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;

        &:hover:not(:disabled) {
            background: #c0392b;
        }

        &:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    }

    &__error {
        padding: 0.5rem 1rem;
        color: #e74c3c;
        font-size: 0.85rem;
    }
}

.version-list {
    list-style: none;
    margin: 0;
    padding: 0;

    &__item {
        display: flex;
        flex-direction: column;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.15s;

        &:hover {
            background: #f8f8f8;
        }

        &--selected {
            background: #eef4ff;
        }
    }

    &__number {
        font-weight: 600;
        font-size: 0.8rem;
        color: #4a90e2;
    }

    &__date {
        font-size: 0.75rem;
        color: #999;
        margin-top: 0.1rem;
    }

    &__title {
        font-size: 0.85rem;
        color: #333;
        margin-top: 0.15rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}

.confirm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
}

.confirm-dialog {
    background: #fff;
    padding: 1.5rem;
    border-radius: 8px;
    width: 320px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);

    p {
        margin: 0 0 1rem;
        font-size: 0.95rem;
    }

    &__actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
}

.btn {
    padding: 0.4rem 1rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    background: #fff;
    font-size: 0.9rem;

    &--primary {
        background: #e74c3c;
        color: #fff;
        border-color: #e74c3c;

        &:hover {
            background: #c0392b;
        }
    }
}
</style>
