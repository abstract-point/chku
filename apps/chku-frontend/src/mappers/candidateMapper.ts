import type { ApiBookCandidate } from '@/api/types'
import type { BookChoiceEvent } from '@/types/club'

export function mapCandidateToChoice(candidate: ApiBookCandidate): BookChoiceEvent {
  return {
    id: candidate.id,
    type: 'book-choice',
    proposerName: candidate.proposer.name,
    bookTitle: candidate.book.title,
    author: candidate.book.author,
    description: candidate.description,
    reason: candidate.reason,
    responses: candidate.responses,
  }
}
