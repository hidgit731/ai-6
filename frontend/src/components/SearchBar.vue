<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSearchStore } from '@/stores/search'

defineProps<{
    autofocus?: boolean
}>()

const router = useRouter()
const searchStore = useSearchStore()

const inputValue = ref('')
let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput(): void {
    if (debounceTimer !== null) {
        clearTimeout(debounceTimer)
    }
    debounceTimer = setTimeout(() => {
        fireSearch()
    }, 300)
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Enter') {
        if (debounceTimer !== null) {
            clearTimeout(debounceTimer)
            debounceTimer = null
        }
        fireSearch()
    }
}

function fireSearch(): void {
    const q = inputValue.value.trim()
    if (q) {
        router.push({ name: 'search', query: { q } })
    } else {
        searchStore.clearResults()
        router.push({ name: 'notes-list' })
    }
}
</script>

<template>
    <div class="search-bar">
        <input
            v-model="inputValue"
            type="search"
            class="search-bar__input"
            placeholder="Поиск по заметкам…"
            :autofocus="autofocus"
            @input="onInput"
            @keydown="onKeydown"
        />
    </div>
</template>

<style scoped lang="scss">
.search-bar {
    flex: 1;
    max-width: 400px;
    margin: 0 1rem;

    &__input {
        width: 100%;
        padding: 0.4rem 0.75rem;
        border: 1px solid var(--color-border, #ddd);
        border-radius: 4px;
        font-size: 0.9rem;
        color: var(--color-text, #333);
        background: var(--color-bg, #fff);
        outline: none;
        transition: border-color 0.15s;

        &:focus {
            border-color: var(--color-primary, #4a90d9);
        }

        &::placeholder {
            color: #aaa;
        }
    }
}
</style>
