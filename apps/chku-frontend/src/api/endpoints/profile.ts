import { http } from '@/api/http'
import type { ApiProfileBook } from '@/api/types'

export const profileApi = {
  async readingHistory() {
    return http.get<unknown, ApiProfileBook[]>('/me/reading-history')
  },
}
