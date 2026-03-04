export interface SearchResultItem {
    id: string
    title: string
    headline: string
    rank: number
}

export interface SearchResultPage {
    items: SearchResultItem[]
    total: number
    page: number
    perPage: number
    totalPages: number
}

const API_BASE = import.meta.env.VITE_API_URL ?? '/api'

export function useSearch() {
    async function search(q: string, page = 1, limit = 20): Promise<SearchResultPage> {
        const url = `${API_BASE}/notes/search?q=${encodeURIComponent(q)}&page=${page}&limit=${limit}`
        const response = await fetch(url)
        if (!response.ok) {
            throw new Error(`Search failed: ${response.status}`)
        }
        return response.json() as Promise<SearchResultPage>
    }

    return { search }
}
