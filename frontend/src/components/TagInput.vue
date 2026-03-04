<script setup lang="ts">
import { ref, computed } from 'vue'
import { useTags } from '@/composables/useTags'
import type { Tag } from '@/composables/useTags'

const props = defineProps<{
    modelValue: string[]
    placeholder?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string[]]
}>()

const tagsApi = useTags()
const inputValue = ref('')
const suggestions = ref<Tag[]>([])
const showDropdown = ref(false)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const showCreateOption = computed(() => {
    const q = inputValue.value.trim()
    if (!q) return false
    return !suggestions.value.some((s) => s.name.toLowerCase() === q.toLowerCase())
})

function onInput(): void {
    const q = inputValue.value.trim()
    if (debounceTimer) clearTimeout(debounceTimer)
    if (!q) {
        suggestions.value = []
        showDropdown.value = false
        return
    }
    showDropdown.value = true
    debounceTimer = setTimeout(async () => {
        try {
            suggestions.value = await tagsApi.suggest(q)
        } catch {
            suggestions.value = []
        }
    }, 300)
}

function addTag(name: string): void {
    const trimmed = name.trim()
    if (!trimmed || props.modelValue.includes(trimmed)) return
    emit('update:modelValue', [...props.modelValue, trimmed])
    inputValue.value = ''
    suggestions.value = []
    showDropdown.value = false
}

function removeTag(name: string): void {
    emit('update:modelValue', props.modelValue.filter((t) => t !== name))
}

function onKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter') {
        e.preventDefault()
        if (inputValue.value.trim()) {
            addTag(inputValue.value)
        }
    }
}

function onBlur(): void {
    setTimeout(() => {
        showDropdown.value = false
    }, 150)
}
</script>

<template>
    <div class="tag-input">
        <div class="tag-input__tags">
            <span v-for="tag in modelValue" :key="tag" class="tag-badge">
                {{ tag }}
                <button class="tag-badge__remove" type="button" @click="removeTag(tag)">×</button>
            </span>
            <div class="tag-input__wrapper">
                <input
                    v-model="inputValue"
                    class="tag-input__field"
                    type="text"
                    :placeholder="placeholder ?? 'Добавить тег...'"
                    @input="onInput"
                    @keydown="onKeydown"
                    @blur="onBlur"
                />
                <ul v-if="showDropdown" class="tag-input__dropdown">
                    <li
                        v-for="s in suggestions"
                        :key="s.id"
                        class="tag-input__suggestion"
                        @mousedown.prevent="addTag(s.name)"
                    >
                        {{ s.name }}
                    </li>
                    <li
                        v-if="showCreateOption"
                        class="tag-input__suggestion tag-input__suggestion--create"
                        @mousedown.prevent="addTag(inputValue)"
                    >
                        Создать «{{ inputValue.trim() }}»
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tag-input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.4rem 0.6rem;
    background: #fff;
}

.tag-input__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    align-items: center;
}

.tag-badge {
    background: #e8f0fb;
    color: #4a90d9;
    border-radius: 3px;
    padding: 0.2rem 0.5rem;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.tag-badge__remove {
    background: none;
    border: none;
    cursor: pointer;
    color: #4a90d9;
    font-size: 1rem;
    line-height: 1;
    padding: 0;
}

.tag-input__wrapper {
    position: relative;
    flex: 1;
    min-width: 120px;
}

.tag-input__field {
    border: none;
    outline: none;
    width: 100%;
    font-size: 0.9rem;
    padding: 0.1rem 0;
    background: transparent;
}

.tag-input__dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    list-style: none;
    margin: 0;
    padding: 0;
    z-index: 100;
    max-height: 200px;
    overflow-y: auto;
}

.tag-input__suggestion {
    padding: 0.4rem 0.75rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.tag-input__suggestion:hover {
    background: #f0f0f0;
}

.tag-input__suggestion--create {
    color: #4a90d9;
    font-style: italic;
}

.tag-input__suggestion--create:hover {
    background: #eef4fc;
}
</style>
