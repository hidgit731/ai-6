<script setup lang="ts">
import MarkdownPreview from '@/components/MarkdownPreview.vue'

const props = defineProps<{
    title: string
    content: string
    wikiLinkMap?: Map<string, string>
}>()

const emit = defineEmits<{
    'update:title': [value: string]
    'update:content': [value: string]
}>()
</script>

<template>
    <div class="markdown-editor">
        <div class="editor-title">
            <input
                class="title-input"
                type="text"
                placeholder="Заголовок заметки"
                :value="props.title"
                @input="emit('update:title', ($event.target as HTMLInputElement).value)"
            />
        </div>
        <div class="editor-panes">
            <div class="editor-pane">
                <label class="pane-label">Редактор</label>
                <textarea
                    class="content-textarea"
                    placeholder="Введите Markdown-текст..."
                    :value="props.content"
                    @input="emit('update:content', ($event.target as HTMLTextAreaElement).value)"
                />
            </div>
            <div class="preview-pane">
                <label class="pane-label">Предпросмотр</label>
                <MarkdownPreview :content="props.content" :wiki-link-map="props.wikiLinkMap" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.markdown-editor {
    display: flex;
    flex-direction: column;
    height: 100%;
    gap: 0.5rem;
}

.editor-title {
    padding: 0.5rem 0;
}

.title-input {
    width: 100%;
    font-size: 1.5rem;
    font-weight: bold;
    border: none;
    border-bottom: 2px solid #e0e0e0;
    padding: 0.5rem 0;
    outline: none;
    background: transparent;
}

.title-input:focus {
    border-bottom-color: #4a90d9;
}

.editor-panes {
    display: flex;
    flex: 1;
    gap: 1rem;
    min-height: 0;
}

.editor-pane,
.preview-pane {
    flex: 1;
    display: flex;
    flex-direction: column;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.pane-label {
    background: #f8f8f8;
    padding: 0.4rem 0.75rem;
    font-size: 0.8rem;
    color: #666;
    border-bottom: 1px solid #e0e0e0;
    font-weight: 500;
}

.content-textarea {
    flex: 1;
    padding: 1rem;
    font-family: monospace;
    font-size: 0.95rem;
    line-height: 1.6;
    border: none;
    resize: none;
    outline: none;
    background: transparent;
}

/* Mobile: vertical layout */
@media (max-width: 767px) {
    .editor-panes {
        flex-direction: column;
    }

    .editor-pane,
    .preview-pane {
        min-height: 200px;
    }
}
</style>
