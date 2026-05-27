import { http } from '@/api/http'
import type { ApiCycle } from '@/api/types'

export type CycleBookPayload = {
  title: string
  author: string
  description?: string | null
  genre_ids?: number[]
  coverColor?: string | null
  coverFile?: File | null
}

export const cycleApi = {
  async list() {
    return http.get<unknown, ApiCycle[]>('/cycles')
  },

  async show(cycleNumber: number | string) {
    return http.get<unknown, ApiCycle>(`/cycles/${cycleNumber}`)
  },

  async updateBook(cycleNumber: number | string, payload: CycleBookPayload) {
    if (payload.coverFile) {
      const fd = new FormData()
      fd.append('title', payload.title)
      fd.append('author', payload.author)
      if (payload.description) fd.append('description', payload.description)
      if (payload.genre_ids?.length) {
        payload.genre_ids.forEach((id) => fd.append('genre_ids[]', String(id)))
      }
      if (payload.coverColor) fd.append('coverColor', payload.coverColor)
      fd.append('coverFile', payload.coverFile)
      return http.patchForm<unknown, ApiCycle>(`/cycles/${cycleNumber}/book`, fd)
    }
    return http.patch<unknown, ApiCycle>(`/cycles/${cycleNumber}/book`, payload)
  },
}
