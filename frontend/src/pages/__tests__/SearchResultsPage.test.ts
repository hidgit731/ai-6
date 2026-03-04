import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

const mockFetchResults = vi.fn()
const mockClearResults = vi.fn()

vi.mock('@/stores/search', () => ({
    useSearchStore: () => ({
        query: 'keyword',
        results: [
            {
                id: 'note-1',
                title: 'Test Note',
                headline: 'found <b>keyword</b> here <script>alert(1)<\/script>',
                rank: 0.9,
            },
        ],
        total: 1,
        page: 1,
        totalPages: 1,
        loading: false,
        error: null,
        fetchResults: mockFetchResults,
        clearResults: mockClearResults,
    }),
}))

vi.mock('@/components/SkeletonList.vue', () => ({
    default: { template: '<div class="skeleton-list" />' },
}))

vi.mock('@/components/Pagination.vue', () => ({
    default: { template: '<div class="pagination" />' },
}))

function makeRouter() {
    return createRouter({
        history: createMemoryHistory('/search?q=keyword'),
        routes: [
            { path: '/search', name: 'search', component: { template: '<div />' } },
            { path: '/notes/:id', name: 'note-view', component: { template: '<div />' } },
        ],
    })
}

describe('SearchResultsPage', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('renders headline with <b> tag preserved', async () => {
        const router = makeRouter()
        await router.push('/search?q=keyword')

        const { default: SearchResultsPage } = await import('@/pages/SearchResultsPage.vue')
        const wrapper = mount(SearchResultsPage, {
            global: { plugins: [router] },
        })

        const headline = wrapper.find('.search-result__headline')
        expect(headline.exists()).toBe(true)
        expect(headline.html()).toContain('<b>keyword</b>')
    })

    it('strips <script> tags from headline (DOMPurify XSS protection)', async () => {
        const router = makeRouter()
        await router.push('/search?q=keyword')

        const { default: SearchResultsPage } = await import('@/pages/SearchResultsPage.vue')
        const wrapper = mount(SearchResultsPage, {
            global: { plugins: [router] },
        })

        const headline = wrapper.find('.search-result__headline')
        expect(headline.html()).not.toContain('<script>')
        expect(headline.html()).not.toContain('alert(1)')
    })
})
