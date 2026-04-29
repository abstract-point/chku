import type { MeetingSummary } from '@/types/dashboard'

export const nextMeeting = {
  id: 'october-42',
  dateLabel: '18 октября',
  dayTimeLabel: 'суббота · 19:00',
  place: 'Библиотека имени Некрасова, зал «Сад».',
  participantInitials: ['ЕЛ', 'МК'],
  extraParticipantsCount: 4,
} satisfies MeetingSummary
