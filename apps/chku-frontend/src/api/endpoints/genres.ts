import { http } from '@/api/http'
import type { ApiGenre } from '@/api/types'

export const genresApi = {
  async list() {
    return http.get<unknown, ApiGenre[]>('/genres')
  },
}
