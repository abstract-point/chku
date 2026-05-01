import { http } from '@/api/http'
import type { ApiMemberBookQueueItem } from '@/api/types'

export type BookQueuePayload = {
  title: string
  author: string
  description?: string
  reason?: string
}

export const bookQueueApi = {
  async list() {
    return http.get<unknown, ApiMemberBookQueueItem[]>('/me/book-queue')
  },

  async create(payload: BookQueuePayload) {
    return http.post<unknown, ApiMemberBookQueueItem>('/me/book-queue', payload)
  },

  async update(id: number, payload: Pick<BookQueuePayload, 'description' | 'reason'>) {
    return http.patch<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}`, payload)
  },

  async remove(id: number) {
    return http.delete<unknown, ApiMemberBookQueueItem>(`/me/book-queue/${id}`)
  },

  async reorder(itemIds: number[]) {
    return http.post<unknown, ApiMemberBookQueueItem[]>('/me/book-queue/reorder', { itemIds })
  },
}
