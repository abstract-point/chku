import { http } from '@/api/http'
import type { ApiOpenLibraryCover } from '@/api/types'

export const bookLookupApi = {
  async listOpenLibraryCovers(title: string, author: string) {
    return http.get<unknown, ApiOpenLibraryCover[]>('/books/open-library/covers', {
      params: { title, author },
    })
  },
}
