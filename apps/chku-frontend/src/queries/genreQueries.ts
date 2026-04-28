import { useQuery } from '@tanstack/vue-query'
import { genresApi } from '@/api/endpoints/genres'
import { queryKeys } from '@/queries/keys'

export function useGenresQuery() {
  return useQuery({
    queryKey: queryKeys.genres,
    queryFn: () => genresApi.list(),
    staleTime: 300_000,
  })
}
