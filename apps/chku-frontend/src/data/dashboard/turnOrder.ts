import type { TurnOrderMember } from '@/types/dashboard'

export const turnOrder = [
  {
    name: '1. Михаил',
    status: 'Текущий',
    isCurrentCycleProposer: true,
    cycleNumber: 42,
  },
  {
    name: '2. Елена',
    status: 'Выбирает следующую',
    active: true,
  },
  {
    name: '3. Анна',
    status: '',
  },
  {
    name: '4. Тимур',
    status: '',
  },
] satisfies TurnOrderMember[]
