import { useQuery } from '@tanstack/vue-query'
import { clubApi } from '@/api/endpoints/club'
import { queryKeys } from '@/queries/keys'

export function useClubQuery() {
  return useQuery({
    queryKey: queryKeys.club,
    queryFn: () => clubApi.show(),
    staleTime: 300_000,
  })
}
