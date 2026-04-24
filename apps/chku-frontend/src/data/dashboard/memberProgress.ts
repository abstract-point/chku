import type { BookProgressMember } from '@/types/dashboard'

export const memberProgress = [
  {
    initials: 'ЕЛ',
    name: 'Елена Воронцова',
    status: 'Закончила',
  },
  {
    initials: 'МК',
    name: 'Михаил Корнев',
    progress: 80,
  },
  {
    initials: 'АС',
    name: 'Анна Соколова',
    badge: 'Читает',
  },
] satisfies BookProgressMember[]
