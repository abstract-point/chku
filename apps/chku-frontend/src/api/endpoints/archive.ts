import { http } from '@/api/http'
import type { ApiArchiveBook } from '@/api/types'

export const archiveApi = {
  async list() {
    return http.get<unknown, ApiArchiveBook[]>('/archive')
  },

  async show(slug: string) {
    return http.get<unknown, ApiArchiveBook>(`/archive/${slug}`)
  },
}
