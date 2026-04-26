import type { ApiMeeting } from '@/api/types'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import type { MeetingDetail } from '@/types/dashboard'

export function mapMeetingDetail(meeting: ApiMeeting): MeetingDetail {
  return {
    id: String(meeting.id),
    title: meeting.title,
    cycleLabel: meeting.cycleLabel ?? 'Цикл',
    dateLabel: formatDateLabel(meeting.date),
    timeLabel: formatTimeLabel(meeting.time),
    place: meeting.place,
    placeAddress: meeting.address,
    placeReservation: meeting.reservation,
    meetingLink: meeting.link,
    topics: meeting.topics ?? [],
    rsvpStatus:
      meeting.rsvps?.find((rsvp) => rsvp.member.email === 'elena@example.com')?.status ?? 'pending',
    attendees:
      meeting.rsvps
        ?.filter((rsvp) => rsvp.status === 'attending')
        .map((rsvp) => ({
          name: rsvp.member.name,
          initials: rsvp.member.initials,
        })) ?? [],
    book: {
      title: meeting.book?.title ?? 'Книга встречи',
      author: meeting.book?.author ?? '',
      cycleSlug: meeting.book?.slug,
    },
  }
}
