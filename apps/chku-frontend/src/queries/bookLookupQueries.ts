import { computed, type MaybeRefOrGetter, toValue } from 'vue'
import { useQuery } from '@tanstack/vue-query'
import { bookLookupApi } from '@/api/endpoints/bookLookup'
import { queryKeys } from '@/queries/keys'

export function useBookCoverSearchQuery(
  title: MaybeRefOrGetter<string>,
  author: MaybeRefOrGetter<string>,
  isbn?: MaybeRefOrGetter<string | null>,
) {
  const cleanTitle = computed(() => toValue(title).trim())
  const cleanAuthor = computed(() => toValue(author).trim())
  const cleanIsbn = computed(() => {
    const val = isbn ? toValue(isbn) : null
    return val?.trim() || null
  })

  return useQuery({
    queryKey: computed(() => queryKeys.bookCoverSearch(cleanTitle.value, cleanAuthor.value, cleanIsbn.value)),
    queryFn: () => bookLookupApi.searchCovers(cleanTitle.value, cleanAuthor.value, cleanIsbn.value),
    enabled: computed(() => {
      if (cleanIsbn.value) return true
      return cleanTitle.value.length > 0 && cleanAuthor.value.length > 0
        && cleanTitle.value.length + cleanAuthor.value.length >= 3
    }),
    staleTime: 5 * 60_000,
  })
}
