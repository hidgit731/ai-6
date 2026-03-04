import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TagCloud from '@/components/TagCloud.vue'
import type { TagCloudItem } from '@/composables/useTags'

function makeTag(name: string, noteCount: number): TagCloudItem {
    return { id: `id-${name}`, name, noteCount, createdAt: '2026-01-01T00:00:00+00:00' }
}

describe('TagCloud', () => {
    it('shows empty state when no tags', () => {
        const wrapper = mount(TagCloud, {
            props: { tags: [], selectedNames: [] },
        })
        expect(wrapper.text()).toContain('Тегов пока нет')
    })

    it('renders tag items', () => {
        const wrapper = mount(TagCloud, {
            props: { tags: [makeTag('work', 5), makeTag('urgent', 2)], selectedNames: [] },
        })
        expect(wrapper.text()).toContain('work')
        expect(wrapper.text()).toContain('urgent')
    })

    it('applies larger font size to tag with more notes', () => {
        const wrapper = mount(TagCloud, {
            props: {
                tags: [makeTag('big', 10), makeTag('small', 1)],
                selectedNames: [],
            },
        })
        const items = wrapper.findAll('.tag-cloud__item')
        const bigFontSize = parseFloat(items[0].attributes('style')?.match(/font-size:\s*([\d.]+)rem/)?.[1] ?? '0')
        const smallFontSize = parseFloat(items[1].attributes('style')?.match(/font-size:\s*([\d.]+)rem/)?.[1] ?? '0')
        expect(bigFontSize).toBeGreaterThan(smallFontSize)
    })

    it('emits tagClick when tag is clicked', async () => {
        const wrapper = mount(TagCloud, {
            props: { tags: [makeTag('work', 3)], selectedNames: [] },
        })
        await wrapper.find('.tag-cloud__item').trigger('click')
        expect(wrapper.emitted('tagClick')).toBeTruthy()
        expect(wrapper.emitted('tagClick')![0]).toEqual(['work'])
    })

    it('marks active tags with active class', () => {
        const wrapper = mount(TagCloud, {
            props: {
                tags: [makeTag('work', 3), makeTag('urgent', 1)],
                selectedNames: ['work'],
            },
        })
        const items = wrapper.findAll('.tag-cloud__item')
        expect(items[0].classes()).toContain('tag-cloud__item--active')
        expect(items[1].classes()).not.toContain('tag-cloud__item--active')
    })
})
