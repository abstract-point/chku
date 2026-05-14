import type { TurnOrderMember } from '@/types/dashboard'

export const turnOrder = [
  {
    name: '1. Павел Иванов',
    initials: '1П',
    isChoosingNow: true,
    hasProposedBook: false,
  },
  {
    name: '2. Марина Светлова',
    initials: '2М',
    active: true,
  },
  {
    name: '3. Алексей Дмитриев',
    initials: '3А',
  },
] satisfies TurnOrderMember[]
