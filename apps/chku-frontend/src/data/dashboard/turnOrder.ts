import type { TurnOrderMember } from '@/types/dashboard'

export const turnOrder = [
  {
    name: '1. Михаил',
    status: 'Текущий',
  },
  {
    name: '2. Елена',
    status: 'Выбирает следующую',
    active: true,
  },
  {
    name: '3. Анна',
    status: 'Ноябрь',
  },
  {
    name: '4. Тимур',
    status: 'Декабрь',
  },
] satisfies TurnOrderMember[]
