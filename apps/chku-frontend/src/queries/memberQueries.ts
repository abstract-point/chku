import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { membersApi } from '@/api/endpoints/members'
import { mapMember } from '@/mappers/memberMapper'
import { queryKeys } from '@/queries/keys'
import { authSessionQueryOptions } from '@/queries/authQueries'

export function useCurrentUserQuery() {
  return useQuery({
    ...authSessionQueryOptions(),
    select: (data) => (data.user ? mapMember(data.user) : null),
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

export function useCreateMemberMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: membersApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.members })
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
    },
  })
}

export function useDeactivateMemberMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (id: number) => membersApi.deactivate(id),
    onSuccess: (_member, id) => {
      queryClient.invalidateQueries({ queryKey: queryKeys.members })
      queryClient.invalidateQueries({ queryKey: queryKeys.member(id) })
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
    },
  })
}
