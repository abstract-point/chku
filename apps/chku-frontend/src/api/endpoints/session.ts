import { http } from '@/api/http'
import type { ApiMember } from '@/api/types'

export const sessionApi = {
  async me() {
    return http.get<unknown, ApiMember>('/me')
  },
}
