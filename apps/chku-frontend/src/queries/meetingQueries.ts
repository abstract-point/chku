import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuery } from '@tanstack/vue-query'
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
