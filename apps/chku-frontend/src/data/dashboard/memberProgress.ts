import type { BookProgressMember } from '@/types/dashboard'

export const memberProgress = [
  {
    name: 'Елена Воронцова',
    status: 'Закончила',
    progress: 100,
    rank: 1,
    medal: 'gold',
    finishedAt: '2024-10-14T09:00:00Z',
  },
  {
    name: 'Михаил Корнев',
    progress: 80,
    rank: 2,
    medal: null,
  },
  {
    name: 'Анна Соколова',
    progress: 48,
    badge: 'Читает',
    rank: 3,
    medal: null,
  },
] satisfies BookProgressMember[]
