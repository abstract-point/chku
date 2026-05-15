import type { TurnOrderMember } from '@/types/dashboard'

export const turnOrder = [
  {
    name: 'Елена Воронцова',
    avatarUrl: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop&crop=face',
    active: true,
    isChoosingNow: true,
    hasProposedBook: false,
  },
  {
    name: 'Михаил Корнев',
    avatarUrl: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face',
    active: true,
  },
  {
    name: 'Алексей Дмитриев',
    avatarUrl: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face',
  },
] satisfies TurnOrderMember[]
