import { http } from '@/api/http'
import type { ApiMemberBookQueueItem } from '@/api/types'

export type BookQueuePayload = {
  title: string
  author: string
  description?: string
  coverFile?: File | null
  genre_ids?: number[]
}

export const bookQueueApi = {
  async list() {
    return http.get<unknown, ApiMemberBookQueueItem[]>('/me/book-queue')
  },

  async listRejected() {
    return http.get<unknown, ApiMemberBookQueueItem[]>('/me/book-queue/rejected')
  },

  async create(payload: BookQueuePayload) {
    if (payload.coverFile) {
      const fd = new FormData()
      fd.append('title', payload.title)
      fd.append('author', payload.author)
      if (payload.description) fd.append('description', payload.description)
      if (payload.genre_ids?.length) {
        payload.genre_ids.forEach((id) => fd.append('genre_ids[]', String(id)))
      }
      fd.append('coverFile', payload.coverFile)
      return http.postForm<unknown, ApiMemberBookQueueItem>('/me/book-queue', fd)
    }
    return http.post<unknown, ApiMemberBookQueueItem>('/me/book-queue', payload)
  },

  async update(id: number, payload: Partial<BookQueuePayload>) {
    if (payload.coverFile) {
      const fd = new FormData()
      if (payload.title) fd.append('title', payload.title)
      if (payload.author) fd.append('author', payload.author)
      if (payload.description != null) fd.append('description', payload.description)
      if (payload.genre_ids?.length) {
        payload.genre_ids.forEach((id) => fd.append('genre_ids[]', String(id)))
      }
      fd.append('coverFile', payload.coverFile)
      return http.patchForm<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}`, fd)
    }
    return http.patch<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}`, payload)
  },

  async remove(id: number) {
    return http.delete<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}`)
  },

  async makeCandidate(id: number) {
    return http.post<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}/candidate`)
  },

  async reorder(itemIds: number[]) {
    return http.post<unknown, ApiMemberBookQueueItem[]>('/me/book-queue/reorder', { itemIds })
  },
}
