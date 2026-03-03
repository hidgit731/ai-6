import { marked } from 'marked'
import DOMPurify from 'dompurify'

export function useMarkdown() {
    function renderMarkdown(content: string): string {
        const rawHtml = marked.parse(content) as string
        return DOMPurify.sanitize(rawHtml)
    }

    return { renderMarkdown }
}
