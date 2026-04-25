import type { MeetingDetail } from '@/types/dashboard'

export const meetingDetail = {
  id: 'october-42',
  title: 'Октябрьская встреча',
  cycleLabel: 'Цикл №42 · Завершение',
  dateLabel: '18 октября',
  timeLabel: '19:00 — 21:00',
  place: 'Библиотека имени Некрасова, зал «Сад»',
  placeAddress: 'ул. Литературная, 123, центр',
  placeReservation: 'Забронировано на имя «ЧКУ»',
  meetingLink: 'zoom.us/j/chku-meeting',
  topics: [
    'Значение Кладбища забытых книг.',
    'Прогрессия характера Даниэля и параллели с Хулианом Караксом.',
    'Изображение послевоенной Барселоны как самостоятельного персонажа.',
    'Темы памяти, одержимости и силы литературы.',
  ],
  rsvpStatus: 'pending',
  attendees: [
    { name: 'Екатерина Л.', initials: 'ЕЛ' },
    { name: 'Михаил К.', initials: 'МК' },
    { name: 'Анна С.', initials: 'АС' },
    { name: 'Дмитрий П.', initials: 'ДП' },
  ],
  book: {
    title: 'Тень ветра',
    author: 'Карлос Руис Сафон',
    cycleSlug: 'cycle-42',
  },
} satisfies MeetingDetail
