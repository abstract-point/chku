import { useQuery } from '@tanstack/vue-query'
import { profileApi } from '@/api/endpoints/profile'
import { queryKeys } from '@/queries/keys'

export function useCurrentUserReadingHistoryQuery() {
  return useQuery({
    queryKey: queryKeys.currentUserReadingHistory,
    queryFn: () => profileApi.readingHistory(),
    staleTime: 60_000,
  })
}
