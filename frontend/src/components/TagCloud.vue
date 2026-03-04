<script setup lang="ts">
import { computed } from 'vue'
import type { TagCloudItem } from '@/composables/useTags'

const props = defineProps<{
    tags: TagCloudItem[]
    selectedNames: string[]
}>()

const emit = defineEmits<{
    tagClick: [name: string]
}>()

const minCount = computed(() => {
    if (props.tags.length === 0) return 0
    return Math.min(...props.tags.map((t) => t.noteCount))
})

const maxCount = computed(() => {
    if (props.tags.length === 0) return 0
    return Math.max(...props.tags.map((t) => t.noteCount))
})

function fontSize(count: number): string {
    if (maxCount.value === minCount.value) return '1.4rem'
    const size = 0.8 + ((count - minCount.value) / (maxCount.value - minCount.value)) * 1.2
    return `${size.toFixed(2)}rem`
}
</script>

<template>
    <div class="tag-cloud">
        <p v-if="tags.length === 0" class="tag-cloud__empty">Тегов пока нет</p>
        <span
            v-for="tag in tags"
            :key="tag.id"
            class="tag-cloud__item"
            :class="{ 'tag-cloud__item--active': selectedNames.includes(tag.name) }"
            :style="{ fontSize: fontSize(tag.noteCount) }"
            @click="emit('tagClick', tag.name)"
        >
            {{ tag.name }}
        </span>
    </div>
</template>

<style scoped>
.tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: baseline;
    padding: 0.5rem 0;
}

.tag-cloud__empty {
    color: #999;
    font-size: 0.9rem;
    margin: 0;
}

.tag-cloud__item {
    cursor: pointer;
    color: #4a90d9;
    transition: color 0.15s;
    line-height: 1.4;
}

.tag-cloud__item:hover {
    color: #357abd;
}

.tag-cloud__item--active {
    font-weight: 600;
    color: #357abd;
    text-decoration: underline;
}
</style>
