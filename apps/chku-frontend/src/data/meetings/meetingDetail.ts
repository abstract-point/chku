import type { MeetingDetail } from '@/types/dashboard'

export const meetingDetail = {
  id: 'october-42',
  title: 'Октябрьская встреча',
  cycleLabel: 'Цикл №42 · Завершение',
  cycleId: 42,
  dateLabel: '18 октября',
  timeLabel: '19:00 — 21:00',
  place: 'Библиотека имени Некрасова, зал «Сад»',
  isOnline: false,
  placeAddress: 'ул. Литературная, 123, центр',
  placeReservation: 'Забронировано на имя «ЧКУ»',
  meetingLink: 'zoom.us/j/chku-meeting',
  status: 'scheduled',
  canStart: true,
  canFinish: false,
  missingRatingMemberIds: [],
  topics: [
    'Значение Кладбища забытых книг.',
    'Прогрессия характера Даниэля и параллели с Хулианом Караксом.',
    'Изображение послевоенной Барселоны как самостоятельного персонажа.',
    'Темы памяти, одержимости и силы литературы.',
  ],
  rsvpStatus: 'pending' as const,
  attendees: [
    { id: 1, name: 'Екатерина Л.', status: 'attending' as const },
    { id: 2, name: 'Михаил К.', status: 'attending' as const },
    { id: 3, name: 'Анна С.', status: 'attending' as const },
    { id: 4, name: 'Дмитрий П.', status: 'attending' as const },
  ],
  book: {
    title: 'Тень ветра',
    author: 'Карлос Руис Сафон',
    cycleSlug: 'cycle-42',
  },
} satisfies MeetingDetail
