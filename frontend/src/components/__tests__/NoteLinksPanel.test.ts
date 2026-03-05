import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import NoteLinksPanel from '@/components/NoteLinksPanel.vue'
import { useNoteLinksStore } from '@/stores/noteLinks'

vi.mock('@/composables/useNoteLinks', () => ({
    useNoteLinks: () => ({
        getLinks: vi.fn(),
        getGraph: vi.fn(),
    }),
}))

function makeRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', component: { template: '<div />' } },
            { path: '/notes/:id', component: { template: '<div />' } },
        ],
    })
}

describe('NoteLinksPanel', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
    })

    it('renders nothing when there are no links', () => {
        const router = makeRouter()
        const wrapper = mount(NoteLinksPanel, {
            props: { noteId: 'note-1' },
            global: { plugins: [router] },
        })
        expect(wrapper.find('details').exists()).toBe(false)
    })

    it('renders panel with incoming links section when incoming.length > 0', () => {
        const router = makeRouter()
        const store = useNoteLinksStore()
        store.currentNoteLinks = {
            incoming: [{ id: 'a', title: 'Alpha' }],
            outgoing: [],
        }
        const wrapper = mount(NoteLinksPanel, {
            props: { noteId: 'note-1' },
            global: { plugins: [router] },
        })
        expect(wrapper.find('details').exists()).toBe(true)
        expect(wrapper.text()).toContain('Alpha')
        expect(wrapper.text()).toContain('Входящие (1)')
        expect(wrapper.text()).toContain('Нет исходящих ссылок')
    })

    it('renders outgoing links as anchors', () => {
        const router = makeRouter()
        const store = useNoteLinksStore()
        store.currentNoteLinks = {
            incoming: [],
            outgoing: [{ id: 'b', title: 'Beta' }],
        }
        const wrapper = mount(NoteLinksPanel, {
            props: { noteId: 'note-1' },
            global: { plugins: [router] },
        })
        const links = wrapper.findAll('a')
        expect(links.length).toBeGreaterThan(0)
        expect(wrapper.text()).toContain('Beta')
        expect(wrapper.text()).toContain('Исходящие (1)')
    })

    it('<details> is open by default when links exist', () => {
        const router = makeRouter()
        const store = useNoteLinksStore()
        store.currentNoteLinks = {
            incoming: [{ id: 'c', title: 'Gamma' }],
            outgoing: [{ id: 'd', title: 'Delta' }],
        }
        const wrapper = mount(NoteLinksPanel, {
            props: { noteId: 'note-1' },
            global: { plugins: [router] },
        })
        const details = wrapper.find('details')
        expect(details.exists()).toBe(true)
        expect(details.attributes('open')).toBeDefined()
    })
})
