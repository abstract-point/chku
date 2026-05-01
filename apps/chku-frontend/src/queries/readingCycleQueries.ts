import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { readingCyclesApi, type RatingReviewPayload } from '@/api/endpoints/readingCycles'
import { queryKeys } from '@/queries/keys'

export function useSaveRatingReviewMutation() {
  return useMutation({
    mutationFn: (payload: RatingReviewPayload) => readingCyclesApi.saveCurrentRatingReview(payload),
  })
}

export function useCompleteCurrentCycleMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: () => readingCyclesApi.completeCurrent(),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.dashboard })
      client.invalidateQueries({ queryKey: queryKeys.activeCandidate })
      client.invalidateQueries({ queryKey: queryKeys.archive })
    },
  })
}
