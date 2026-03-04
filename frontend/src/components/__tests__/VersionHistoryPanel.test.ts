import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import VersionHistoryPanel from '@/components/VersionHistoryPanel.vue'
import { useNoteVersionsStore } from '@/stores/noteVersions'

class MockIntersectionObserver {
    observe = vi.fn()
    disconnect = vi.fn()
    unobserve = vi.fn()
    constructor(_callback: IntersectionObserverCallback) {}
}

vi.stubGlobal('IntersectionObserver', MockIntersectionObserver)

vi.mock('@/composables/useNoteVersions', () => ({
    useNoteVersions: () => ({
        listVersions: vi.fn().mockResolvedValue({ items: [], total: 0, page: 1, perPage: 20, totalPages: 1 }),
        getVersion: vi.fn(),
        revertVersion: vi.fn(),
    }),
}))

vi.mock('@/stores/notes', () => ({
    useNotesStore: () => ({
        currentNote: null,
    }),
}))

function makeVersion(num: number) {
    return {
        id: `version-id-${num}`,
        noteId: 'note-id-1',
        title: `Title at version ${num}`,
        content: `Content at version ${num}`,
        versionNumber: num,
        createdAt: '2026-03-01T10:00:00+00:00',
    }
}

describe('VersionHistoryPanel', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
    })

    it('renders version list when versions exist', () => {
        const store = useNoteVersionsStore()
        store.versions = [makeVersion(2), makeVersion(1)]
        store.isPanelOpen = true

        const wrapper = mount(VersionHistoryPanel, {
            props: {
                noteId: 'note-id-1',
                currentTitle: 'Current Title',
                currentContent: 'Current Content',
            },
        })

        expect(wrapper.text()).toContain('v2')
        expect(wrapper.text()).toContain('v1')
    })

    it('shows empty state when no versions and not loading', () => {
        const store = useNoteVersionsStore()
        store.versions = []
        store.loading = false
        store.isPanelOpen = true

        const wrapper = mount(VersionHistoryPanel, {
            props: {
                noteId: 'note-id-1',
                currentTitle: 'Current Title',
                currentContent: 'Current Content',
            },
        })

        expect(wrapper.text()).toContain('No versions yet')
    })

    it('close button calls store.closePanel()', async () => {
        const store = useNoteVersionsStore()
        store.versions = []
        store.isPanelOpen = true

        const wrapper = mount(VersionHistoryPanel, {
            props: {
                noteId: 'note-id-1',
                currentTitle: 'Title',
                currentContent: null,
            },
        })

        const closeBtn = wrapper.find('.version-panel__close')
        await closeBtn.trigger('click')
        expect(store.isPanelOpen).toBe(false)
    })
})
