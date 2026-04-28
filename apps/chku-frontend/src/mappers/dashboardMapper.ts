import type { ApiDashboard, ApiMeeting } from '@/api/types'
import { formatDateLabel } from '@/mappers/date'
import type { MeetingSummary } from '@/types/dashboard'

export function mapMeetingSummary(meeting: ApiMeeting): MeetingSummary {
  const initials = meeting.rsvps?.map((rsvp) => rsvp.member.initials).filter(Boolean) ?? []

  return {
    id: String(meeting.id),
    dateLabel: formatDateLabel(meeting.date),
    place: meeting.place,
    participantInitials: initials.slice(0, 4),
    extraParticipantsCount: Math.max(0, initials.length - 4),
  }
}

export function mapDashboard(dto: ApiDashboard) {
  return {
    ...dto,
    memberProgress: dto.memberProgress ?? [],
    turnOrder: dto.turnOrder ?? [],
    clubStats: dto.clubStats ?? [],
    nextMeeting: dto.nextMeeting ? mapMeetingSummary(dto.nextMeeting) : null,
  }
}
