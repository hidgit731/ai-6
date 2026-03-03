<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
    page: number
    totalPages: number
}>()

const emit = defineEmits<{
    'page-change': [page: number]
}>()

const pages = computed(() => {
    const result: number[] = []
    for (let i = 1; i <= props.totalPages; i++) {
        result.push(i)
    }
    return result
})

const hasPrev = computed(() => props.page > 1)
const hasNext = computed(() => props.page < props.totalPages)
</script>

<template>
    <nav v-if="totalPages > 1" class="pagination" aria-label="Пагинация">
        <button
            class="page-btn"
            :disabled="!hasPrev"
            @click="emit('page-change', props.page - 1)"
        >
            ←
        </button>

        <button
            v-for="p in pages"
            :key="p"
            class="page-btn"
            :class="{ active: p === props.page }"
            @click="emit('page-change', p)"
        >
            {{ p }}
        </button>

        <button
            class="page-btn"
            :disabled="!hasNext"
            @click="emit('page-change', props.page + 1)"
        >
            →
        </button>
    </nav>
</template>

<style scoped>
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    padding: 1rem 0;
}

.page-btn {
    min-width: 2.2rem;
    height: 2.2rem;
    padding: 0 0.5rem;
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    color: #333;
    transition: background 0.1s, border-color 0.1s;
}

.page-btn:hover:not(:disabled):not(.active) {
    background: #f5f5f5;
    border-color: #bbb;
}

.page-btn.active {
    background: #4a90d9;
    color: #fff;
    border-color: #4a90d9;
    cursor: default;
}

.page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
</style>
