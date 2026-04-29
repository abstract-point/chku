import { http } from '@/api/http'
import type { ApiReadingProgress } from '@/api/types'

export type ReadingProgressPayload = {
  progressPercent: number
}

export const readingProgressApi = {
  async updateCurrent(payload: ReadingProgressPayload) {
    return http.patch<unknown, ApiReadingProgress>('/reading-progress/me', payload)
  },
}
