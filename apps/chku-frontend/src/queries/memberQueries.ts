import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { authApi } from '@/api/endpoints/auth'
import { membersApi } from '@/api/endpoints/members'
import { mapMember } from '@/mappers/memberMapper'
import { queryKeys } from '@/queries/keys'

export function useCurrentUserQuery() {
  return useQuery({
    queryKey: queryKeys.currentUser,
    queryFn: async () => {
      const data = await authApi.me()
      return data.user ? mapMember(data.user) : null
    },
    staleTime: 60_000,
  })
}

export function useMembersQuery() {
  return useQuery({
    queryKey: queryKeys.members,
    queryFn: async () => (await membersApi.list()).map(mapMember),
    staleTime: 60_000,
  })
}

export function useMemberQuery(id: MaybeRefOrGetter<number>) {
  return useQuery({
    queryKey: computed(() => queryKeys.member(toValue(id))),
    queryFn: async () => mapMember(await membersApi.show(toValue(id))),
    enabled: computed(() => Number.isFinite(toValue(id))),
    staleTime: 60_000,
  })
}
