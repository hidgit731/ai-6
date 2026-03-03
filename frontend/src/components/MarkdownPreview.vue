<script setup lang="ts">
import { computed } from 'vue'
import { useMarkdown } from '@/composables/useMarkdown'

const props = defineProps<{
    content: string
}>()

const { renderMarkdown } = useMarkdown()

const renderedHtml = computed(() => renderMarkdown(props.content))
</script>

<template>
    <div class="markdown-preview" v-html="renderedHtml" />
</template>

<style scoped>
.markdown-preview {
    padding: 1rem;
    min-height: 100%;
    line-height: 1.6;
    overflow-y: auto;
}

.markdown-preview :deep(h1),
.markdown-preview :deep(h2),
.markdown-preview :deep(h3),
.markdown-preview :deep(h4),
.markdown-preview :deep(h5),
.markdown-preview :deep(h6) {
    margin-top: 1rem;
    margin-bottom: 0.5rem;
    font-weight: bold;
}

.markdown-preview :deep(p) {
    margin-bottom: 0.75rem;
}

.markdown-preview :deep(code) {
    background: #f4f4f4;
    padding: 0.1em 0.3em;
    border-radius: 3px;
    font-family: monospace;
}

.markdown-preview :deep(pre) {
    background: #f4f4f4;
    padding: 1rem;
    border-radius: 4px;
    overflow-x: auto;
}

.markdown-preview :deep(ul),
.markdown-preview :deep(ol) {
    padding-left: 1.5rem;
    margin-bottom: 0.75rem;
}

.markdown-preview :deep(blockquote) {
    border-left: 4px solid #ccc;
    padding-left: 1rem;
    color: #666;
    margin: 0.75rem 0;
}

.markdown-preview :deep(a) {
    color: #4a90d9;
    text-decoration: underline;
}
</style>
