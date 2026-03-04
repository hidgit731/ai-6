import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useTags } from '@/composables/useTags'
import type { TagCloudItem } from '@/composables/useTags'

export const useTagsStore = defineStore('tags', () => {
    const tagsApi = useTags()

    const tags = ref<TagCloudItem[]>([])
    const selectedTagNames = ref<string[]>([])

    async function fetchCloud(): Promise<void> {
        try {
            tags.value = await tagsApi.list()
        } catch {
            // silently ignore fetch errors for cloud
        }
    }

    function selectTag(name: string): void {
        if (!selectedTagNames.value.includes(name)) {
            selectedTagNames.value = [...selectedTagNames.value, name]
        }
    }

    function deselectTag(name: string): void {
        selectedTagNames.value = selectedTagNames.value.filter((t) => t !== name)
    }

    function clearTags(): void {
        selectedTagNames.value = []
    }

    function initFromUrl(tagNames: string | string[] | undefined): void {
        const newNames = !tagNames ? [] : Array.isArray(tagNames) ? [...tagNames] : [tagNames]
        const current = selectedTagNames.value
        if (newNames.length === current.length && newNames.every((n, i) => n === current[i])) return
        selectedTagNames.value = newNames
    }

    // [SC-005] Deletes tag via API, refreshes cloud, and deselects the tag from active filters.
    // UI components must call this instead of useTags().remove() directly.
    async function deleteTag(id: string, name: string): Promise<void> {
        await tagsApi.remove(id)
        await fetchCloud()
        deselectTag(name)
    }

    return {
        tags,
        selectedTagNames,
        fetchCloud,
        selectTag,
        deselectTag,
        clearTags,
        initFromUrl,
        deleteTag,
    }
})
