import { http } from '@/api/http'
import type { ApiMember } from '@/api/types'

export const membersApi = {
  async list() {
    return http.get<unknown, ApiMember[]>('/members')
  },

  async show(id: number) {
    return http.get<unknown, ApiMember>(`/members/${id}`)
  },

  async create(payload: {
    name: string
    email: string
    password: string
    initials: string
    favorite_genre_id: number | null
    joined_at: string
    role: 'member' | 'admin' | 'developer'
  }) {
    return http.post<unknown, ApiMember>('/members', payload)
  },

  async deactivate(id: number) {
    return http.post<unknown, ApiMember>(`/members/${id}/deactivate`)
  },
}
