import type { ApiDashboard, ApiMeeting } from '@/api/types'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import type { MeetingSummary } from '@/types/dashboard'

export function mapMeetingSummary(meeting: ApiMeeting): MeetingSummary {
  const weekday = meeting.date
    ? new Intl.DateTimeFormat('ru-RU', { weekday: 'long' }).format(new Date(`${meeting.date}T00:00:00`))
    : 'день уточняется'

  return {
    id: String(meeting.id),
    dateLabel: formatDateLabel(meeting.date),
    dayTimeLabel: `${weekday} · ${formatTimeLabel(meeting.time)}`,
    place: meeting.place,
    isOnline: meeting.isOnline ?? false,
    link: meeting.link,
    attendees:
      meeting.rsvps?.map((rsvp) => ({
        id: rsvp.member.id,
        name: rsvp.member.name,
        initials: rsvp.member.initials,
        status: rsvp.status,
        favoriteGenre: rsvp.member.favoriteGenre,
        memberSince: rsvp.member.memberSince,
      })) ?? [],
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
