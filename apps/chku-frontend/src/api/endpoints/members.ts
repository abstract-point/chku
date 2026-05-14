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
    avatar?: File | null
    favorite_genre_id: number | null
    joined_at: string
    role: 'member' | 'admin' | 'developer'
  }) {
    if (!payload.avatar) {
      const { avatar: _avatar, ...jsonPayload } = payload
      return http.post<unknown, ApiMember>('/members', jsonPayload)
    }

    const formData = new FormData()
    formData.set('name', payload.name)
    formData.set('email', payload.email)
    formData.set('password', payload.password)
    formData.set('joined_at', payload.joined_at)
    formData.set('role', payload.role)
    if (payload.favorite_genre_id !== null) {
      formData.set('favorite_genre_id', String(payload.favorite_genre_id))
    }
    formData.set('avatar', payload.avatar)

    return http.post<unknown, ApiMember>('/members', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  async deactivate(id: number) {
    return http.post<unknown, ApiMember>(`/members/${id}/deactivate`)
  },
}
