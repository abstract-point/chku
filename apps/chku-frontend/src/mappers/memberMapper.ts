import type { ApiMember } from '@/api/types'
import type { MemberProfile } from '@/types/club'

export function mapMember(member: ApiMember): MemberProfile {
  return {
    id: member.id,
    name: member.name,
    avatarUrl: member.avatarUrl ?? null,
    memberSince: member.memberSince,
    isActive: member.isActive,
    email: member.email,
    favoriteGenreId: member.favoriteGenreId ?? null,
    favoriteGenre: member.favoriteGenre,
    stats: member.stats ?? {
      read: 0,
      proposed: 0,
      meetings: 0,
      goldOwls: 0,
      silverOwls: 0,
      bronzeOwls: 0,
    },
    readingHistory: member.readingHistory ?? [],
  }
}
