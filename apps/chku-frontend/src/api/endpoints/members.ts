import { http } from '@/api/http'
import type { ApiMember } from '@/api/types'

export const membersApi = {
  async list() {
    return http.get<unknown, ApiMember[]>('/members')
  },

  async show(id: number) {
    return http.get<unknown, ApiMember>(`/members/${id}`)
  },
}
