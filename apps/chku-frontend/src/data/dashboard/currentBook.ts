import type { CurrentBook } from '@/types/dashboard'

export const currentBook = {
  title: 'Тень ветра',
  coverTitle: 'Тень\nветра',
  author: 'Карлос Руис Сафон',
  selectedBy: 'Михаил',
  description:
    'В послевоенной Барселоне мальчик находит таинственную книгу на Кладбище забытых книг. Эта находка ведёт его в историю о памяти, любви и старых тайнах города.',
  progress: 65,
  progressLabel: '65% · глава 12',
} satisfies CurrentBook
