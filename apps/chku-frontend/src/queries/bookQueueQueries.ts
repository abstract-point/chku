import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { computed } from 'vue'
import { bookQueueApi, type BookQueuePayload } from '@/api/endpoints/bookQueue'
import type { ApiMemberBookQueueItem } from '@/api/types'
import { queryKeys } from '@/queries/keys'
import type { BookQueueItem } from '@/types/club'

function mapQueueItem(item: ApiMemberBookQueueItem): BookQueueItem {
  return {
    id: item.id,
    position: item.position,
    status: item.status,
    title: item.book.title,
    author: item.book.author,
    description: item.description ?? item.book.description,
    reason: item.reason,
  }
}

function invalidateQueue(client: ReturnType<typeof useQueryClient>) {
  client.invalidateQueries({ queryKey: queryKeys.bookQueue })
  client.invalidateQueries({ queryKey: queryKeys.dashboard })
}

export function useBookQueueQuery() {
  const query = useQuery({
    queryKey: queryKeys.bookQueue,
    queryFn: bookQueueApi.list,
    staleTime: 30_000,
  })

  return {
    ...query,
    items: computed(() => query.data.value?.map(mapQueueItem) ?? []),
  }
}

export function useCreateBookQueueItemMutation() {
  const client = useQueryClient()
  return useMutation({
    mutationFn: (payload: BookQueuePayload) => bookQueueApi.create(payload),
    onSuccess: () => invalidateQueue(client),
  })
}

export function useRemoveBookQueueItemMutation() {
  const client = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => bookQueueApi.remove(id),
    onSuccess: () => invalidateQueue(client),
  })
}

export function useReorderBookQueueMutation() {
  const client = useQueryClient()
  return useMutation({
    mutationFn: (ids: number[]) => bookQueueApi.reorder(ids),
    onSuccess: () => invalidateQueue(client),
  })
}
