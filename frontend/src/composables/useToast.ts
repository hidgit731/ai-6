import { ref } from 'vue'

export interface Toast {
    id: number
    message: string
    type: 'success' | 'error' | 'info'
}

const toasts = ref<Toast[]>([])
let counter = 0

export function useToast() {
    function addToast(message: string, type: Toast['type'] = 'info'): void {
        const id = ++counter
        toasts.value.push({ id, message, type })
        setTimeout(() => removeToast(id), 4000)
    }

    function removeToast(id: number): void {
        const idx = toasts.value.findIndex((t) => t.id === id)
        if (idx !== -1) toasts.value.splice(idx, 1)
    }

    return { toasts, addToast, removeToast }
}
