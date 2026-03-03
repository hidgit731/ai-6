<script setup lang="ts">
import { useToast } from '@/composables/useToast'

const { toasts, removeToast } = useToast()
</script>

<template>
    <div class="toast-container">
        <transition-group name="toast">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="toast"
                :class="`toast--${toast.type}`"
                @click="removeToast(toast.id)"
            >
                {{ toast.message }}
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-container {
    position: fixed;
    bottom: 1.5rem;
    right: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    z-index: 300;
    pointer-events: none;
}

.toast {
    pointer-events: all;
    padding: 0.65rem 1rem;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-width: 320px;
}

.toast--info {
    background: #333;
}

.toast--success {
    background: #38a169;
}

.toast--error {
    background: #e53e3e;
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.25s, transform 0.25s;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(0.5rem);
}
</style>
