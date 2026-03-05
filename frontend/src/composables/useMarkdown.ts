import { marked } from 'marked'
import DOMPurify from 'dompurify'

export function useMarkdown() {
    function applyWikiLinks(content: string, map: Map<string, string>): string {
        if (map.size === 0 && !content.includes('[[')) {
            return content
        }

        // Mask fenced code blocks to avoid replacing wiki-links inside them
        const fencedPlaceholders: string[] = []
        const withoutFenced = content.replace(/```[\s\S]*?```/gu, (match) => {
            fencedPlaceholders.push(match)
            return `\x00FENCED${fencedPlaceholders.length - 1}\x00`
        })

        // Mask inline code spans
        const inlinePlaceholders: string[] = []
        const withoutInline = withoutFenced.replace(/`[^`]*`/gu, (match) => {
            inlinePlaceholders.push(match)
            return `\x00INLINE${inlinePlaceholders.length - 1}\x00`
        })

        // Replace [[Title]] patterns
        const replaced = withoutInline.replace(/\[\[([^\[\]]+)\]\]/gu, (_match, title: string) => {
            const noteId = map.get(title)
            if (noteId) {
                return `<a href="/notes/${noteId}" class="wiki-link" data-note-id="${noteId}">${title}</a>`
            }
            return `<span class="wiki-link wiki-link--unresolved">${title}</span>`
        })

        // Restore inline code
        const restoredInline = replaced.replace(/\x00INLINE(\d+)\x00/g, (_, i) => inlinePlaceholders[Number(i)])

        // Restore fenced code blocks
        return restoredInline.replace(/\x00FENCED(\d+)\x00/g, (_, i) => fencedPlaceholders[Number(i)])
    }

    function renderMarkdown(content: string, wikiLinkMap: Map<string, string> = new Map()): string {
        const withWikiLinks = applyWikiLinks(content, wikiLinkMap)
        const rawHtml = marked.parse(withWikiLinks) as string
        return DOMPurify.sanitize(rawHtml, {
            ADD_ATTR: ['data-note-id'],
        })
    }

    return { renderMarkdown, applyWikiLinks }
}
