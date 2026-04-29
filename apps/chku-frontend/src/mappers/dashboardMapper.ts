import type { ApiDashboard, ApiMeeting } from '@/api/types'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import type { MeetingSummary } from '@/types/dashboard'

export function mapMeetingSummary(meeting: ApiMeeting): MeetingSummary {
  const initials = meeting.rsvps?.map((rsvp) => rsvp.member.initials).filter(Boolean) ?? []
  const weekday = meeting.date
    ? new Intl.DateTimeFormat('ru-RU', { weekday: 'long' }).format(new Date(`${meeting.date}T00:00:00`))
    : 'день уточняется'

  return {
    id: String(meeting.id),
    dateLabel: formatDateLabel(meeting.date),
    dayTimeLabel: `${weekday} · ${formatTimeLabel(meeting.time)}`,
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
