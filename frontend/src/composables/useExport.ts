import { ref } from 'vue'

const isPdfLoading = ref(false)
const pdfError = ref<string | null>(null)

export function useExport() {
  async function downloadMarkdown(noteId: string): Promise<void> {
    const response = await fetch(`/api/notes/${noteId}/export/markdown`)
    if (!response.ok) {
      throw new Error('Failed to download Markdown file')
    }

    const disposition = response.headers.get('Content-Disposition') ?? ''
    const filenameMatch = disposition.match(/filename="([^"]+)"/)
    const filename = filenameMatch ? filenameMatch[1] : 'note.md'

    const blob = await response.blob()
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }

  async function downloadPdf(noteId: string): Promise<void> {
    isPdfLoading.value = true
    pdfError.value = null

    try {
      const response = await fetch(`/api/notes/${noteId}/export/pdf`)
      if (!response.ok) {
        throw new Error('Failed to generate PDF')
      }

      const disposition = response.headers.get('Content-Disposition') ?? ''
      const filenameMatch = disposition.match(/filename="([^"]+)"/)
      const filename = filenameMatch ? filenameMatch[1] : 'note.pdf'

      const blob = await response.blob()
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      a.click()
      URL.revokeObjectURL(url)
    } catch (error) {
      pdfError.value = error instanceof Error ? error.message : 'Не удалось создать PDF'
    } finally {
      isPdfLoading.value = false
    }
  }

  return { downloadMarkdown, downloadPdf, isPdfLoading, pdfError }
}
