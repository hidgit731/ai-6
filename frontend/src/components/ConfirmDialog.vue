<script setup lang="ts">
defineProps<{
    title: string
    message: string
    confirmLabel?: string
}>()

const emit = defineEmits<{
    confirm: []
    cancel: []
}>()
</script>

<template>
    <div class="dialog-overlay" @click.self="emit('cancel')">
        <div class="dialog" role="dialog" aria-modal="true">
            <h2 class="dialog-title">{{ title }}</h2>
            <p class="dialog-message">{{ message }}</p>
            <div class="dialog-actions">
                <button class="btn-cancel" @click="emit('cancel')">Отмена</button>
                <button class="btn-confirm" @click="emit('confirm')">
                    {{ confirmLabel ?? 'Подтвердить' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.dialog {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.dialog-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin: 0 0 0.75rem;
    color: #222;
}

.dialog-message {
    font-size: 0.95rem;
    color: #555;
    margin: 0 0 1.25rem;
    line-height: 1.5;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.btn-cancel {
    background: none;
    border: 1px solid #ddd;
    padding: 0.5rem 1.1rem;
    border-radius: 4px;
    cursor: pointer;
    color: #555;
}

.btn-cancel:hover {
    background: #f5f5f5;
}

.btn-confirm {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 0.5rem 1.1rem;
    border-radius: 4px;
    cursor: pointer;
}

.btn-confirm:hover {
    background: #c0392b;
}
</style>
