import type { BookChoiceEvent } from '@/types/club'

export const activeBookChoice = {
  type: 'book-choice',
  proposerName: 'Елена',
  bookTitle: 'Тайную историю',
} satisfies BookChoiceEvent
