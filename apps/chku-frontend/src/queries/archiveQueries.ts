import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { archiveApi } from '@/api/endpoints/archive'
import { queryKeys } from '@/queries/keys'

export function useArchiveQuery() {
  return useQuery({
    queryKey: queryKeys.archive,
    queryFn: () => archiveApi.list(),
    staleTime: 60_000,
  })
}

export function useArchiveBookQuery(slug: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: computed(() => queryKeys.archiveBook(toValue(slug))),
    queryFn: () => archiveApi.show(toValue(slug)),
    enabled: computed(() => toValue(slug).length > 0),
    staleTime: 60_000,
  })
}
