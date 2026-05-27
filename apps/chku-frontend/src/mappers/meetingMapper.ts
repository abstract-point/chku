import type { ApiMeeting } from '@/api/types'
import i18n from '@/i18n'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import type { MeetingDetail } from '@/types/dashboard'

export function mapMeetingDetail(meeting: ApiMeeting, currentUserId?: number): MeetingDetail {
  const allRsvps =
    meeting.rsvps?.map((rsvp) => ({
      id: rsvp.member.id,
      name: rsvp.member.name,
      avatarUrl: rsvp.member.avatarUrl ?? null,
      status: rsvp.status,
      favoriteGenres: rsvp.member.favoriteGenres ?? [],
      memberSince: rsvp.member.memberSince,
      isAdmin: rsvp.member.isAdmin,
    })) ?? []

  return {
    id: String(meeting.id),
    title: meeting.title,
    cycleLabel: meeting.cycleLabel ?? i18n.global.t('dates.fallbackCycle'),
    cycleId: meeting.cycleId ?? 0,
    dateLabel: formatDateLabel(meeting.date),
    timeLabel: formatTimeLabel(meeting.time),
    place: meeting.place,
    isOnline: meeting.isOnline ?? false,
    placeAddress: meeting.address,
    placeReservation: meeting.reservation,
    meetingLink: meeting.link,
    discussion: meeting.discussion ?? [],
    status: meeting.status ?? 'scheduled',
    canStart: meeting.canStart ?? false,
    canFinish: meeting.canFinish ?? false,
    missingRatingMemberIds: meeting.missingRatingMemberIds ?? [],
    missingReadingMemberIds: meeting.missingReadingMemberIds ?? [],
    date: meeting.date,
    time: meeting.time,
    rsvpStatus: currentUserId
      ? (allRsvps.find((a) => a.id === currentUserId)?.status ?? 'pending')
      : 'pending',
    attendees: allRsvps.filter((a) => a.status === 'attending'),
    book: {
      title: meeting.book?.title ?? i18n.global.t('dates.fallbackBook'),
      author: meeting.book?.author ?? '',
      cycleSlug: meeting.book?.slug,
    },
    ratings: meeting.ratings ?? [],
    reviews: meeting.reviews ?? [],
  }
}
