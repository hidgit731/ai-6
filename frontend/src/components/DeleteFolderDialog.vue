<script setup lang="ts">
defineProps<{
    hasNotes: boolean
    hasFolders: boolean
}>()

const emit = defineEmits<{
    confirm: [strategy: 'move_to_root' | 'delete_recursive']
    cancel: []
}>()

import { ref } from 'vue'

const strategy = ref<'move_to_root' | 'delete_recursive'>('move_to_root')
</script>

<template>
    <div class="dialog-overlay" @click.self="emit('cancel')">
        <div class="dialog">
            <h3 class="dialog__title">Удалить папку</h3>
            <p class="dialog__message">
                Папка содержит
                <span v-if="hasNotes">заметки</span>
                <span v-if="hasNotes && hasFolders"> и </span>
                <span v-if="hasFolders">вложенные папки</span>.
                Выберите действие:
            </p>

            <div class="dialog__options">
                <label class="dialog__option">
                    <input v-model="strategy" type="radio" value="move_to_root" />
                    Переместить содержимое в «Без папки»
                </label>
                <label class="dialog__option">
                    <input v-model="strategy" type="radio" value="delete_recursive" />
                    Удалить всё рекурсивно
                </label>
            </div>

            <div class="dialog__actions">
                <button class="btn btn--cancel" @click="emit('cancel')">Отмена</button>
                <button class="btn btn--danger" @click="emit('confirm', strategy)">Удалить</button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.dialog-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 200;
}

.dialog {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    width: 420px;
    max-width: 90vw;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.dialog__title {
    margin: 0 0 0.75rem;
    font-size: 1.1rem;
}

.dialog__message {
    margin: 0 0 1rem;
    color: #555;
    font-size: 0.95rem;
}

.dialog__options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
}

.dialog__option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.dialog__actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.btn {
    padding: 0.45rem 1rem;
    border-radius: 4px;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn--cancel {
    background: #f5f5f5;
    border-color: #ddd;
    color: #333;
}

.btn--danger {
    background: #e53e3e;
    color: #fff;
}

.btn--danger:hover {
    background: #c53030;
}
</style>
