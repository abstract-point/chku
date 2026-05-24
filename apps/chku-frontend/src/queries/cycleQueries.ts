import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { cycleApi, type CycleBookPayload } from '@/api/endpoints/cycles'
import { queryKeys } from '@/queries/keys'

export function useCyclesQuery() {
  return useQuery({
    queryKey: queryKeys.cycles,
    queryFn: () => cycleApi.list(),
    staleTime: 60_000,
  })
}

export function useCycleQuery(cycleNumber: MaybeRefOrGetter<string | number>) {
  return useQuery({
    queryKey: computed(() => queryKeys.cycle(toValue(cycleNumber))),
    queryFn: () => cycleApi.show(toValue(cycleNumber)),
    enabled: computed(() => String(toValue(cycleNumber)).length > 0),
    staleTime: 60_000,
  })
}

export function useUpdateCycleBookMutation(cycleNumber: MaybeRefOrGetter<string | number>) {
  const client = useQueryClient()
  return useMutation({
    mutationFn: (payload: CycleBookPayload) => cycleApi.updateBook(toValue(cycleNumber), payload),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.cycles })
      client.invalidateQueries({ queryKey: queryKeys.cycle(toValue(cycleNumber)) })
      client.invalidateQueries({ queryKey: queryKeys.dashboard })
      client.invalidateQueries({ queryKey: queryKeys.bookQueue })
      client.invalidateQueries({ queryKey: queryKeys.currentUserReadingHistory })
      client.invalidateQueries({ queryKey: queryKeys.members })
    },
  })
}
