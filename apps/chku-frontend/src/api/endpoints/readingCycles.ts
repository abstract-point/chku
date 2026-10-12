import { http } from '@/api/http'

export type RatingReviewPayload = {
  rating: number
  review?: string
}

export const readingCyclesApi = {
  async saveCurrentRatingReview(payload: RatingReviewPayload) {
    return http.put('/reading-cycles/current/rating-review', payload)
  },
}
