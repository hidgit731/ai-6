import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TagInput from '@/components/TagInput.vue'

const mockSuggest = vi.hoisted(() =>
    vi.fn().mockResolvedValue([
        { id: '1', name: 'work', createdAt: '2026-01-01T00:00:00+00:00' },
        { id: '2', name: 'workflow', createdAt: '2026-01-01T00:00:00+00:00' },
    ]),
)

vi.mock('@/composables/useTags', () => ({
    useTags: () => ({
        suggest: mockSuggest,
        create: vi.fn().mockResolvedValue({ id: '3', name: 'new', noteCount: 0, createdAt: '' }),
        list: vi.fn().mockResolvedValue([]),
        remove: vi.fn().mockResolvedValue(undefined),
    }),
}))

describe('TagInput', () => {
    beforeEach(() => {
        vi.useFakeTimers()
        mockSuggest.mockClear()
    })

    it('calls suggest with debounce on input', async () => {
        const wrapper = mount(TagInput, {
            props: { modelValue: [] },
        })
        const input = wrapper.find('input')
        await input.setValue('wo')
        vi.advanceTimersByTime(300)
        await vi.runAllTimersAsync()
        expect(mockSuggest).toHaveBeenCalledWith('wo')
    })

    it('adds tag to modelValue on Enter', async () => {
        const wrapper = mount(TagInput, {
            props: { modelValue: [] },
        })
        const input = wrapper.find('input')
        await input.setValue('urgent')
        await input.trigger('keydown', { key: 'Enter' })
        expect(wrapper.emitted('update:modelValue')).toBeTruthy()
        expect(wrapper.emitted('update:modelValue')![0]).toEqual([['urgent']])
    })

    it('removes tag when × is clicked', async () => {
        const wrapper = mount(TagInput, {
            props: { modelValue: ['work', 'urgent'] },
        })
        const removeButtons = wrapper.findAll('.tag-badge__remove')
        await removeButtons[0].trigger('click')
        expect(wrapper.emitted('update:modelValue')![0]).toEqual([['urgent']])
    })

    it('does not add duplicate tag', async () => {
        const wrapper = mount(TagInput, {
            props: { modelValue: ['work'] },
        })
        const input = wrapper.find('input')
        await input.setValue('work')
        await input.trigger('keydown', { key: 'Enter' })
        expect(wrapper.emitted('update:modelValue')).toBeFalsy()
    })

    it('shows dropdown with suggestions when suggest returns results', async () => {
        const wrapper = mount(TagInput, {
            props: { modelValue: [] },
        })
        const input = wrapper.find('input')
        await input.setValue('wo')
        vi.advanceTimersByTime(300)
        await vi.runAllTimersAsync()
        const dropdown = wrapper.find('.tag-input__dropdown')
        expect(dropdown.exists()).toBe(true)
        expect(dropdown.text()).toContain('work')
    })
})
