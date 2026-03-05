import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useExport } from './useExport'

describe('useExport', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    // Reset module state between tests by reimporting
  })

  describe('downloadPdf', () => {
    it('sets isPdfLoading to true during fetch and false after', async () => {
      const { downloadPdf, isPdfLoading } = useExport()

      let resolveResponse!: (v: unknown) => void
      const fetchPromise = new Promise(resolve => { resolveResponse = resolve })
      vi.stubGlobal('fetch', vi.fn(() => fetchPromise))

      const blobUrl = 'blob:test'
      vi.stubGlobal('URL', {
        createObjectURL: vi.fn(() => blobUrl),
        revokeObjectURL: vi.fn(),
      })

      const anchor = { href: '', download: '', click: vi.fn() }
      vi.spyOn(document, 'createElement').mockReturnValue(anchor as unknown as HTMLElement)

      expect(isPdfLoading.value).toBe(false)

      const downloadTask = downloadPdf('test-note-id')

      // Loading should be true while fetch is in-flight
      expect(isPdfLoading.value).toBe(true)

      // Resolve the fetch with a mock response object
      const mockBlob = new Blob(['%PDF'], { type: 'application/pdf' })
      resolveResponse({
        ok: true,
        headers: { get: vi.fn(() => 'attachment; filename="note.pdf"') },
        blob: vi.fn(() => Promise.resolve(mockBlob)),
      })

      await downloadTask

      expect(isPdfLoading.value).toBe(false)
    })

    it('sets pdfError and resets isPdfLoading on fetch failure', async () => {
      const { downloadPdf, isPdfLoading, pdfError } = useExport()

      vi.stubGlobal('fetch', vi.fn(() => Promise.reject(new Error('Network error'))))

      await downloadPdf('bad-note-id')

      expect(isPdfLoading.value).toBe(false)
      expect(pdfError.value).toBe('Network error')
    })

    it('sets pdfError on non-ok HTTP response', async () => {
      const { downloadPdf, pdfError } = useExport()

      vi.stubGlobal('fetch', vi.fn(() => Promise.resolve({ ok: false, status: 500 })))

      await downloadPdf('error-note-id')

      expect(pdfError.value).toBeTruthy()
    })
  })

  describe('downloadMarkdown', () => {
    it('triggers file download for markdown', async () => {
      const { downloadMarkdown } = useExport()

      const mockBlob = new Blob(['# Title'], { type: 'text/markdown' })
      const mockResponse = {
        ok: true,
        headers: { get: vi.fn(() => 'attachment; filename="my-note.md"') },
        blob: vi.fn(() => Promise.resolve(mockBlob)),
      }
      vi.stubGlobal('fetch', vi.fn(() => Promise.resolve(mockResponse)))

      const blobUrl = 'blob:test-md'
      vi.stubGlobal('URL', {
        createObjectURL: vi.fn(() => blobUrl),
        revokeObjectURL: vi.fn(),
      })

      const clickMock = vi.fn()
      vi.spyOn(document, 'createElement').mockReturnValue({
        href: '',
        download: '',
        click: clickMock,
      } as unknown as HTMLElement)

      await downloadMarkdown('note-id-123')

      expect(clickMock).toHaveBeenCalled()
    })
  })
})
