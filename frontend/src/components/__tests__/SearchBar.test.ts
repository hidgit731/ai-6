import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import { createPinia, setActivePinia } from 'pinia'

const clearResultsMock = vi.hoisted(() => vi.fn())

vi.mock('@/stores/search', () => ({
    useSearchStore: () => ({
        clearResults: clearResultsMock,
    }),
}))

function makeRouter() {
    return createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/', name: 'notes-list', component: { template: '<div />' } },
            { path: '/search', name: 'search', component: { template: '<div />' } },
        ],
    })
}

describe('SearchBar', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.useFakeTimers()
        clearResultsMock.mockClear()
    })

    it('does not navigate within 299ms of typing', async () => {
        const router = makeRouter()
        const pushSpy = vi.spyOn(router, 'push')

        const { default: SearchBar } = await import('@/components/SearchBar.vue')
        const wrapper = mount(SearchBar, {
            global: { plugins: [router] },
        })

        await wrapper.find('input').setValue('hello')
        await wrapper.find('input').trigger('input')

        vi.advanceTimersByTime(299)

        expect(pushSpy).not.toHaveBeenCalled()
    })

    it('navigates to search after 300ms pause', async () => {
        const router = makeRouter()
        const pushSpy = vi.spyOn(router, 'push')

        const { default: SearchBar } = await import('@/components/SearchBar.vue')
        const wrapper = mount(SearchBar, {
            global: { plugins: [router] },
        })

        await wrapper.find('input').setValue('hello')
        await wrapper.find('input').trigger('input')

        vi.advanceTimersByTime(300)

        expect(pushSpy).toHaveBeenCalledWith({ name: 'search', query: { q: 'hello' } })
    })

    it('rapid typing only triggers one push after final pause', async () => {
        const router = makeRouter()
        const pushSpy = vi.spyOn(router, 'push')

        const { default: SearchBar } = await import('@/components/SearchBar.vue')
        const wrapper = mount(SearchBar, {
            global: { plugins: [router] },
        })

        await wrapper.find('input').setValue('h')
        await wrapper.find('input').trigger('input')
        vi.advanceTimersByTime(100)

        await wrapper.find('input').setValue('he')
        await wrapper.find('input').trigger('input')
        vi.advanceTimersByTime(100)

        await wrapper.find('input').setValue('hello')
        await wrapper.find('input').trigger('input')
        vi.advanceTimersByTime(300)

        expect(pushSpy).toHaveBeenCalledTimes(1)
        expect(pushSpy).toHaveBeenCalledWith({ name: 'search', query: { q: 'hello' } })
    })

    it('clearing input calls clearResults and redirects to notes list', async () => {
        const router = makeRouter()
        const pushSpy = vi.spyOn(router, 'push')

        const { default: SearchBar } = await import('@/components/SearchBar.vue')
        const wrapper = mount(SearchBar, {
            global: { plugins: [router] },
        })

        await wrapper.find('input').setValue('')
        await wrapper.find('input').trigger('input')
        vi.advanceTimersByTime(300)

        expect(clearResultsMock).toHaveBeenCalled()
        expect(pushSpy).toHaveBeenCalledWith({ name: 'notes-list' })
    })
})
