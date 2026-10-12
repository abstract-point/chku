import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { readingCyclesApi, type RatingReviewPayload } from '@/api/endpoints/readingCycles'
import { queryKeys } from '@/queries/keys'

export function useSaveRatingReviewMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: (payload: RatingReviewPayload) => readingCyclesApi.saveCurrentRatingReview(payload),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.dashboard })
      client.invalidateQueries({ queryKey: ['meetings'] })
      client.invalidateQueries({ queryKey: queryKeys.cycles })
    },
  })
}
