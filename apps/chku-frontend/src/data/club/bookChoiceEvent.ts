import type { BookChoiceEvent } from '@/types/club'

export const activeBookChoice = {
  type: 'book-choice',
  proposerName: 'Елена',
  bookTitle: 'Тайную историю',
  author: 'Донна Тартт',
  description: 'Роман о закрытом круге студентов, античной культуре и последствиях одного решения.',
  reason: 'Подойдёт для обсуждения ответственности, дружбы и того, как интеллектуальная среда меняет людей.',
} satisfies BookChoiceEvent
