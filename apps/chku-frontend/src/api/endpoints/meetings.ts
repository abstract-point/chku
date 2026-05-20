import { http } from '@/api/http'
import type { ApiMeeting } from '@/api/types'

export type CreateMeetingPayload = {
  reading_cycle_id: number
  title: string
  date: string
  time: string
  is_online: boolean
  place?: string
  address?: string
  reservation?: string
  link?: string
  topics?: string[]
  notes?: string
}

export type UpdateMeetingPayload = Partial<CreateMeetingPayload> & {
  rescheduleReason?: string
}

export const meetingsApi = {
  async show(id: string | number) {
    return http.get<unknown, ApiMeeting>(`/meetings/${id}`)
  },

  async store(payload: CreateMeetingPayload) {
    return http.post<unknown, ApiMeeting>('/meetings', payload)
  },

  async update(id: string | number, payload: UpdateMeetingPayload) {
    return http.patch<unknown, ApiMeeting>(`/meetings/${id}`, payload)
  },

  async start(id: string | number) {
    return http.post<unknown, ApiMeeting>(`/meetings/${id}/start`)
  },

  async finish(id: string | number) {
    return http.post<unknown, ApiMeeting>(`/meetings/${id}/finish`)
  },

  async updateMyRsvp(id: string | number, status: 'attending' | 'not_attending' | 'pending') {
    return http.patch<unknown, ApiMeeting>(`/meetings/${id}/rsvps/me`, { status })
  },

  async removeRsvp(meetingId: string | number, memberId: number) {
    return http.delete<unknown, { message: string }>(`/meetings/${meetingId}/rsvps/${memberId}`)
  },

  async addTopic(id: string | number, topic: string) {
    return http.post<unknown, ApiMeeting>(`/meetings/${id}/topics`, { topic })
  },
}
