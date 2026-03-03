<script setup lang="ts">
withDefaults(defineProps<{
    count?: number
}>(), {
    count: 5,
})
</script>

<template>
    <ul class="skeleton-list">
        <li v-for="i in count" :key="i" class="skeleton-list__item">
            <span class="skeleton-list__line skeleton-list__line--short" />
        </li>
    </ul>
</template>

<style scoped>
.skeleton-list {
    list-style: none;
    margin: 0;
    padding: 0.25rem 0;
}

.skeleton-list__item {
    padding: 0.3rem 0.75rem;
}

.skeleton-list__line {
    display: block;
    height: 0.85rem;
    border-radius: 4px;
    background: linear-gradient(90deg, #eee 25%, #ddd 50%, #eee 75%);
    background-size: 200% 100%;
    animation: shimmer 1.4s infinite;
}

.skeleton-list__line--short {
    width: 70%;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
</style>
