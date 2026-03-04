import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import VersionDiffView from '@/components/VersionDiffView.vue'

function makeVersion(title: string, content: string | null = null) {
    return {
        id: 'v-id-1',
        noteId: 'note-id-1',
        title,
        content,
        versionNumber: 1,
        createdAt: '2026-03-01T10:00:00+00:00',
    }
}

describe('VersionDiffView', () => {
    it('renders added words with diff-added class', () => {
        const wrapper = mount(VersionDiffView, {
            props: {
                currentTitle: 'Hello',
                currentContent: null,
                selectedVersion: makeVersion('Hello World'),
            },
        })

        const addedSpans = wrapper.findAll('.diff-added')
        expect(addedSpans.length).toBeGreaterThan(0)
        const addedText = addedSpans.map((s) => s.text()).join('')
        expect(addedText).toContain('World')
    })

    it('renders removed words with diff-removed class', () => {
        const wrapper = mount(VersionDiffView, {
            props: {
                currentTitle: 'Hello World',
                currentContent: null,
                selectedVersion: makeVersion('Hello'),
            },
        })

        const removedSpans = wrapper.findAll('.diff-removed')
        expect(removedSpans.length).toBeGreaterThan(0)
        const removedText = removedSpans.map((s) => s.text()).join('')
        expect(removedText).toContain('World')
    })

    it('renders no highlighted spans when content is identical', () => {
        const wrapper = mount(VersionDiffView, {
            props: {
                currentTitle: 'Same Title',
                currentContent: 'Same Content',
                selectedVersion: makeVersion('Same Title', 'Same Content'),
            },
        })

        expect(wrapper.findAll('.diff-added').length).toBe(0)
        expect(wrapper.findAll('.diff-removed').length).toBe(0)
    })

    it('handles null content gracefully', () => {
        const wrapper = mount(VersionDiffView, {
            props: {
                currentTitle: 'Title',
                currentContent: null,
                selectedVersion: makeVersion('Title', null),
            },
        })

        expect(wrapper.findAll('.diff-added').length).toBe(0)
        expect(wrapper.findAll('.diff-removed').length).toBe(0)
    })
})
