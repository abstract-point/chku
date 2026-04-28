import { http } from '@/api/http'
import type { ApiMeeting } from '@/api/types'

export const meetingsApi = {
  async show(id: string | number) {
    return http.get<unknown, ApiMeeting>(`/meetings/${id}`)
  },
}
