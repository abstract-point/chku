import { http } from '@/api/http'
import type { ApiMeeting } from '@/api/types'

export const meetingsApi = {
  async show(id: string | number) {
    return http.get<unknown, ApiMeeting>(`/meetings/${id}`)
  },

  async updateMyRsvp(id: string | number, status: 'attending' | 'not_attending' | 'pending') {
    return http.patch<unknown, ApiMeeting>(`/meetings/${id}/rsvps/me`, { status })
  },

  async addTopic(id: string | number, topic: string) {
    return http.post<unknown, ApiMeeting>(`/meetings/${id}/topics`, { topic })
  },
}
