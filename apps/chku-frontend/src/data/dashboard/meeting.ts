import type { MeetingSummary } from '@/types/dashboard'

export const nextMeeting = {
  id: 'october-42',
  dateLabel: '18 октября',
  dayTimeLabel: 'суббота · 19:00',
  place: 'Библиотека имени Некрасова, зал «Сад»',
  isOnline: false,
  link: 'zoom.us/j/chku-meeting',
  attendees: [
    { id: 1, name: 'Екатерина Л.', status: 'attending' as const },
    { id: 2, name: 'Михаил К.', status: 'attending' as const },
    { id: 3, name: 'Анна С.', status: 'attending' as const },
    { id: 4, name: 'Дмитрий П.', status: 'attending' as const },
    { id: 5, name: 'Ольга В.', status: 'attending' as const },
    { id: 6, name: 'Сергей Н.', status: 'not_attending' as const },
  ],
} satisfies MeetingSummary
