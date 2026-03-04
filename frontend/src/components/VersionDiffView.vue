<script setup lang="ts">
import { computed } from 'vue'
import { diffWords } from 'diff'
import type { NoteVersion } from '@/composables/useNoteVersions'

const props = defineProps<{
    currentTitle: string
    currentContent: string | null
    selectedVersion: NoteVersion
}>()

const titleChanges = computed(() =>
    diffWords(props.currentTitle, props.selectedVersion.title),
)

const contentChanges = computed(() =>
    diffWords(props.currentContent ?? '', props.selectedVersion.content ?? ''),
)
</script>

<template>
    <div class="version-diff-view">
        <div class="diff-section">
            <h4 class="diff-section__heading">Title</h4>
            <p class="diff-section__content">
                <span
                    v-for="(part, i) in titleChanges"
                    :key="i"
                    :class="{
                        'diff-added': part.added,
                        'diff-removed': part.removed,
                    }"
                >{{ part.value }}</span>
            </p>
        </div>

        <div class="diff-section">
            <h4 class="diff-section__heading">Content</h4>
            <pre class="diff-section__content">
                <span
                    v-for="(part, i) in contentChanges"
                    :key="i"
                    :class="{
                        'diff-added': part.added,
                        'diff-removed': part.removed,
                    }"
                >{{ part.value }}</span>
            </pre>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.version-diff-view {
    padding: 1rem;
    font-size: 0.9rem;
}

.diff-section {
    margin-bottom: 1rem;

    &__heading {
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #888;
        margin-bottom: 0.25rem;
    }

    &__content {
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
        font-family: inherit;
    }
}

.diff-added {
    background-color: #d4f7dc;
    color: #1a6e2e;
}

.diff-removed {
    background-color: #fdd;
    color: #8b0000;
    text-decoration: line-through;
}
</style>
