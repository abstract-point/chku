import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { meetingsApi } from '@/api/endpoints/meetings'
import { mapMeetingDetail } from '@/mappers/meetingMapper'
import { queryKeys } from '@/queries/keys'

export function useMeetingQuery(id: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => queryKeys.meeting(toValue(id))),
    queryFn: async () => mapMeetingDetail(await meetingsApi.show(toValue(id))),
    enabled: computed(() => toValue(id).length > 0),
    staleTime: 60_000,
  })
}

export function useUpdateMeetingRsvpMutation(id: MaybeRefOrGetter<string>) {
  const client = useQueryClient()

  return useMutation({
    mutationFn: (status: 'attending' | 'not_attending' | 'pending') =>
      meetingsApi.updateMyRsvp(toValue(id), status),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.meeting(toValue(id)) })
      client.invalidateQueries({ queryKey: queryKeys.dashboard })
    },
  })
}

export function useAddMeetingTopicMutation(id: MaybeRefOrGetter<string>) {
  const client = useQueryClient()

  return useMutation({
    mutationFn: (topic: string) => meetingsApi.addTopic(toValue(id), topic),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.meeting(toValue(id)) })
    },
  })
}
