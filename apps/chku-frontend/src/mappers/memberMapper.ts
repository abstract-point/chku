import type { ApiMember } from '@/api/types'
import type { MemberProfile } from '@/types/club'

export function mapMember(member: ApiMember): MemberProfile {
  return {
    id: member.id,
    name: member.name,
    initials: member.initials,
    memberSince: member.memberSince,
    isActive: member.isActive,
    email: member.email,
    favoriteGenre: member.favoriteGenre,
    stats: member.stats ?? {
      read: 0,
      proposed: 0,
      meetings: 0,
    },
    readingHistory: member.readingHistory ?? [],
  }
}

