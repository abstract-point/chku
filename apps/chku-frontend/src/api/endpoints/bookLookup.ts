import { http } from '@/api/http'
import type { ApiBookCoverSearchResult } from '@/api/types'

export const bookLookupApi = {
  async searchCovers(title: string, author: string, isbn?: string | null) {
    return http.get<unknown, ApiBookCoverSearchResult[]>('/books/covers/search', {
      params: { title, author, isbn: isbn || undefined },
    })
  },
}
