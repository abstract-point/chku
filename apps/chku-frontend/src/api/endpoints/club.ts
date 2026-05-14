import { http } from '@/api/http'
import type { ApiClub } from '@/api/types'

export const clubApi = {
  async show() {
    return http.get<unknown, ApiClub>('/club')
  },
}
