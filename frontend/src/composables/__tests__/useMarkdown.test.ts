import { describe, it, expect } from 'vitest'
import { useMarkdown } from '@/composables/useMarkdown'

describe('useMarkdown - applyWikiLinks', () => {
    const { applyWikiLinks } = useMarkdown()

    it('replaces [[Title]] with anchor when map has matching entry', () => {
        const map = new Map([['My Note', 'abc-123']])
        const result = applyWikiLinks('See [[My Note]] here', map)
        expect(result).toContain('<a href="/notes/abc-123" class="wiki-link" data-note-id="abc-123">My Note</a>')
    })

    it('replaces [[Unknown]] with unresolved span when not in map', () => {
        const result = applyWikiLinks('See [[Unknown]] here', new Map())
        expect(result).toContain('<span class="wiki-link wiki-link--unresolved">Unknown</span>')
    })

    it('does not convert [[Code]] inside inline code backticks', () => {
        const result = applyWikiLinks('Use `[[InlineCode]]` for this', new Map())
        expect(result).toContain('`[[InlineCode]]`')
        expect(result).not.toContain('wiki-link')
    })

    it('does not convert [[Fence]] inside fenced code block', () => {
        const content = "Normal\n```\n[[FenceCode]]\n```\nAfter"
        const result = applyWikiLinks(content, new Map())
        expect(result).toContain('[[FenceCode]]')
        expect(result).not.toContain('wiki-link')
    })

    it('converts multiple links in one note', () => {
        const map = new Map([
            ['Alpha', 'id-1'],
            ['Beta', 'id-2'],
        ])
        const result = applyWikiLinks('[[Alpha]] and [[Beta]]', map)
        expect(result).toContain('href="/notes/id-1"')
        expect(result).toContain('href="/notes/id-2"')
    })

    it('returns unchanged content when map is empty and no wiki-links', () => {
        const content = 'Just plain text'
        const result = applyWikiLinks(content, new Map())
        expect(result).toBe(content)
    })
})

describe('useMarkdown - renderMarkdown with wikiLinkMap', () => {
    const { renderMarkdown } = useMarkdown()

    it('renders wiki-link anchor in HTML output', () => {
        const map = new Map([['Target', 'note-id-99']])
        const result = renderMarkdown('See [[Target]]', map)
        expect(result).toContain('class="wiki-link"')
        expect(result).toContain('data-note-id="note-id-99"')
    })

    it('renders unresolved span for unknown link', () => {
        const result = renderMarkdown('See [[Ghost]]', new Map())
        expect(result).toContain('class="wiki-link wiki-link--unresolved"')
    })

    it('renders plain markdown without wiki-links when map is empty and no [[ ]] present', () => {
        const result = renderMarkdown('**bold** text', new Map())
        expect(result).toContain('<strong>bold</strong>')
        expect(result).not.toContain('wiki-link')
    })
})
