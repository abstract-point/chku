import type { ApiDashboard, ApiMeeting } from '@/api/types'
import i18n from '@/i18n'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import type { MeetingSummary } from '@/types/dashboard'

export function mapMeetingSummary(meeting: ApiMeeting): MeetingSummary {
  const weekday = meeting.date
    ? new Intl.DateTimeFormat('ru-RU', { weekday: 'long' }).format(
        new Date(`${meeting.date}T00:00:00`),
      )
    : i18n.global.t('dates.unknownDay')

  return {
    id: String(meeting.id),
    title: meeting.title,
    dateLabel: formatDateLabel(meeting.date),
    dayTimeLabel: `${weekday} · ${formatTimeLabel(meeting.time)}`,
    date: meeting.date,
    time: meeting.time,
    place: meeting.place,
    isOnline: meeting.isOnline ?? false,
    link: meeting.link,
    status: meeting.status ?? 'scheduled',
    canStart: meeting.canStart ?? false,
    canFinish: meeting.canFinish ?? false,
    attendees:
      meeting.rsvps?.map((rsvp) => ({
        id: rsvp.member.id,
        name: rsvp.member.name,
        avatarUrl: rsvp.member.avatarUrl ?? null,
        status: rsvp.status,
        favoriteGenres: rsvp.member.favoriteGenres ?? [],
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
