import type { TurnOrderMember } from '@/types/dashboard'

export const turnOrder = [
  {
    name: '1. Елена Воронцова',
    active: true,
    isChoosingNow: true,
    hasProposedBook: false,
  },
  {
    name: '2. Михаил Корнев',
    active: true,
  },
  {
    name: '3. Алексей Дмитриев',
  },
] satisfies TurnOrderMember[]
