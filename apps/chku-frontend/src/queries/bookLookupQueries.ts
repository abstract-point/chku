import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { bookLookupApi } from '@/api/endpoints/bookLookup'
import { queryKeys } from '@/queries/keys'

export function useOpenLibraryCoversQuery(
  title: MaybeRefOrGetter<string>,
  author: MaybeRefOrGetter<string>,
) {
  const cleanTitle = computed(() => toValue(title).trim())
  const cleanAuthor = computed(() => toValue(author).trim())

  return useQuery({
    queryKey: computed(() => queryKeys.openLibraryCovers(cleanTitle.value, cleanAuthor.value)),
    queryFn: () => bookLookupApi.listOpenLibraryCovers(cleanTitle.value, cleanAuthor.value),
    enabled: computed(() => {
      return cleanTitle.value.length > 0 && cleanAuthor.value.length > 0
        && cleanTitle.value.length + cleanAuthor.value.length >= 3
    }),
    staleTime: 5 * 60_000,
  })
}
