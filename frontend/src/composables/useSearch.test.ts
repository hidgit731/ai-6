import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useSearch } from './useSearch'

describe('useSearch', () => {
    beforeEach(() => {
        vi.restoreAllMocks()
    })

    it('calls GET /api/notes/search with encoded query and defaults', async () => {
        const mockResponse = {
            items: [],
            total: 0,
            page: 1,
            perPage: 20,
            totalPages: 1,
        }

        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve(mockResponse),
        })
        vi.stubGlobal('fetch', fetchMock)

        const { search } = useSearch()
        const result = await search('проект')

        expect(fetchMock).toHaveBeenCalledOnce()
        const calledUrl = fetchMock.mock.calls[0][0] as string
        expect(calledUrl).toContain('q=%D0%BF%D1%80%D0%BE%D0%B5%D0%BA%D1%82')
        expect(calledUrl).toContain('page=1')
        expect(calledUrl).toContain('limit=20')
        expect(result).toEqual(mockResponse)
    })

    it('calls API even for empty query (composable does not guard)', async () => {
        const mockResponse = {
            items: [],
            total: 0,
            page: 1,
            perPage: 20,
            totalPages: 1,
        }

        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: () => Promise.resolve(mockResponse),
        })
        vi.stubGlobal('fetch', fetchMock)

        const { search } = useSearch()
        const result = await search('')

        expect(fetchMock).toHaveBeenCalledOnce()
        expect(result).toEqual(mockResponse)
    })

    it('throws on non-ok response', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: false,
            status: 422,
        })
        vi.stubGlobal('fetch', fetchMock)

        const { search } = useSearch()
        await expect(search('query')).rejects.toThrow('Search failed: 422')
    })
})
