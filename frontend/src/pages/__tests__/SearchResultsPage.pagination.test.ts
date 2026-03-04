import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

const mockFetchResults = vi.fn()
const mockClearResults = vi.fn()

vi.mock('@/stores/search', () => ({
    useSearchStore: () => ({
        query: 'test',
        results: [
            { id: 'note-1', title: 'Note 1', headline: '<b>test</b>', rank: 0.9 },
        ],
        total: 60,
        page: 1,
        totalPages: 3,
        loading: false,
        error: null,
        fetchResults: mockFetchResults,
        clearResults: mockClearResults,
    }),
}))

vi.mock('@/components/SkeletonList.vue', () => ({
    default: { template: '<div class="skeleton-list" />' },
}))

const paginationEmit = vi.fn()
vi.mock('@/components/Pagination.vue', () => ({
    default: {
        props: ['page', 'totalPages'],
        emits: ['page-change'],
        template: '<div class="pagination" @click="$emit(\'page-change\', 2)" />',
    },
}))

function makeRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/search', name: 'search', component: { template: '<div />' } },
            { path: '/notes/:id', name: 'note-view', component: { template: '<div />' } },
        ],
    })
}

describe('SearchResultsPage pagination', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('renders Pagination component when totalPages > 1', async () => {
        const router = makeRouter()
        await router.push('/search?q=test&page=1')

        const { default: SearchResultsPage } = await import('@/pages/SearchResultsPage.vue')
        const wrapper = mount(SearchResultsPage, {
            global: { plugins: [router] },
        })

        expect(wrapper.find('.pagination').exists()).toBe(true)
    })

    it('calls fetchResults with new page on page-change event', async () => {
        const router = makeRouter()
        await router.push('/search?q=test&page=1')

        const { default: SearchResultsPage } = await import('@/pages/SearchResultsPage.vue')
        const wrapper = mount(SearchResultsPage, {
            global: { plugins: [router] },
        })

        await wrapper.find('.pagination').trigger('click')
        await vi.waitFor(() => {
            expect(mockFetchResults).toHaveBeenCalledWith('test', 2)
        })
    })
})
