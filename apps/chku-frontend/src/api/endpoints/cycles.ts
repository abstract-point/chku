import { http } from '@/api/http'
import type { ApiCycle } from '@/api/types'

export type CycleBookPayload = {
  title: string
  author: string
  description?: string | null
  genreId?: number | null
  coverColor?: string | null
  coverUrl?: string | null
}

export const cycleApi = {
  async list() {
    return http.get<unknown, ApiCycle[]>('/cycles')
  },

  async show(cycleNumber: number | string) {
    return http.get<unknown, ApiCycle>(`/cycles/${cycleNumber}`)
  },

  async updateBook(cycleNumber: number | string, payload: CycleBookPayload) {
    return http.patch<unknown, ApiCycle>(`/cycles/${cycleNumber}/book`, payload)
  },
}
