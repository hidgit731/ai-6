import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

const getLinksMock = vi.hoisted(() => vi.fn())
const getGraphMock = vi.hoisted(() => vi.fn())

vi.mock('@/composables/useNoteLinks', () => ({
    useNoteLinks: () => ({
        getLinks: getLinksMock,
        getGraph: getGraphMock,
    }),
}))

describe('useNoteLinksStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        getLinksMock.mockReset()
        getGraphMock.mockReset()
    })

    it('fetchLinks populates currentNoteLinks', async () => {
        const { useNoteLinksStore } = await import('@/stores/noteLinks')
        const store = useNoteLinksStore()

        const mockResponse = {
            incoming: [{ id: 'in-1', title: 'Incoming Note' }],
            outgoing: [{ id: 'out-1', title: 'Outgoing Note' }],
        }
        getLinksMock.mockResolvedValue(mockResponse)

        await store.fetchLinks('note-id-1')

        expect(getLinksMock).toHaveBeenCalledWith('note-id-1')
        expect(store.currentNoteLinks).toEqual(mockResponse)
    })

    it('clearLinks resets currentNoteLinks to null', async () => {
        const { useNoteLinksStore } = await import('@/stores/noteLinks')
        const store = useNoteLinksStore()

        store.currentNoteLinks = {
            incoming: [{ id: 'x', title: 'X' }],
            outgoing: [],
        }

        store.clearLinks()

        expect(store.currentNoteLinks).toBeNull()
    })

    it('fetchLinks starts with null state', async () => {
        const { useNoteLinksStore } = await import('@/stores/noteLinks')
        const store = useNoteLinksStore()

        expect(store.currentNoteLinks).toBeNull()
    })
})
