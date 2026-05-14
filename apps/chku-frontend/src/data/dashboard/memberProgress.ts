import type { BookProgressMember } from '@/types/dashboard'

export const memberProgress = [
  {
    name: 'Елена Воронцова',
    status: 'Закончила',
  },
  {
    name: 'Михаил Корнев',
    progress: 80,
  },
  {
    name: 'Анна Соколова',
    badge: 'Читает',
  },
] satisfies BookProgressMember[]
