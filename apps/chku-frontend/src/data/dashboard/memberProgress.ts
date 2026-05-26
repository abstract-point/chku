import type { BookProgressMember } from '@/types/dashboard'

export const memberProgress = [
  {
    id: 1,
    name: 'Елена Воронцова',
    status: 'Закончила',
    progress: 100,
    rank: 1,
    medal: 'gold',
    finishedAt: '2024-10-14T09:00:00Z',
  },
  {
    id: 2,
    name: 'Михаил Корнев',
    progress: 80,
    rank: 2,
    medal: null,
  },
  {
    id: 3,
    name: 'Анна Соколова',
    progress: 48,
    badge: 'Читает',
    rank: 3,
    medal: null,
  },
] satisfies BookProgressMember[]
