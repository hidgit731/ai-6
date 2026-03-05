<script setup lang="ts">
defineProps<{
  label: string
  value: number
}>()
</script>

<template>
    <div class="stat-card">
        <div class="stat-card__value">{{ value }}</div>
        <div class="stat-card__label">{{ label }}</div>
    </div>
</template>

<style scoped>
.stat-card {
    background: var(--color-background-soft, #f8f9fa);
    border: 1px solid var(--color-border, #dee2e6);
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    min-width: 140px;
}

.stat-card__value {
    font-size: 36px;
    font-weight: 700;
    color: var(--color-heading, #2c3e50);
    line-height: 1;
    margin-bottom: 8px;
}

.stat-card__label {
    font-size: 14px;
    color: var(--color-text, #666);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>
