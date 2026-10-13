// Mirrors backend validation rules (max:N) so forms can show counters and stop input early.
export const FIELD_LIMITS = {
  shortText: 255,
  bookDescription: 500,
  discussionMessage: 2000,
  review: 10000,
  genres: 5,
} as const

// Single-line fields show their counter only when the value gets close to the limit.
export const SHORT_FIELD_COUNTER_THRESHOLD = 0.8
