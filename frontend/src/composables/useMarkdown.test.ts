import { describe, it, expect } from 'vitest'
import { useMarkdown } from './useMarkdown'

describe('useMarkdown', () => {
    const { renderMarkdown } = useMarkdown()

    it('renders bold Markdown correctly', () => {
        const result = renderMarkdown('**bold**')
        expect(result).toContain('<strong>bold</strong>')
    })

    it('sanitizes XSS: <script> tags are removed', () => {
        const malicious = '<script>alert(1)</script>'
        const result = renderMarkdown(malicious)
        expect(result).not.toContain('<script>')
        expect(result).not.toContain('alert(1)')
    })

    it('sanitizes XSS in Markdown content', () => {
        const malicious = 'Hello\n\n<script>alert("xss")</script>\n\nWorld'
        const result = renderMarkdown(malicious)
        expect(result).not.toContain('<script>')
        expect(result).not.toContain('alert')
    })

    it('renders headers correctly', () => {
        const result = renderMarkdown('# Hello World')
        expect(result).toContain('<h1>')
        expect(result).toContain('Hello World')
    })
})
