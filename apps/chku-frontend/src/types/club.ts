export type BookChoiceEvent = {
  id: number
  type: 'book-choice'
  proposerName: string
  bookTitle: string
  author: string
  description: string
  reason: string
  responses?: {
    id: number
    member: {
      id: number
      name: string
      initials: string
    }
    response: 'not_read' | 'read' | 'pending'
  }[]
  status?: 'pending' | 'awaiting_owner_confirmation' | 'approved' | 'rejected'
  canConfirm?: boolean
}

export type BookProposalForm = {
  title: string
  author: string
  description: string
  reason: string
}

export type BookQueueItem = {
  id: number
  position: number
  status: 'queued' | 'in_verification' | 'approved' | 'rejected' | 'removed'
  title: string
  author: string
  description?: string | null
  reason?: string | null
}

export type MemberProfile = {
  id: number
  name: string
  initials: string
  memberSince: string
  isActive: boolean
  email: string
  favoriteGenreId?: number | null
  favoriteGenre: string
  stats: {
    read: number
    proposed: number
    meetings: number
  }
  readingHistory: ProfileBook[]
}

export type ProfileBook = {
  title: string
  coverTitle: string
  author: string
  completedLabel: string
  proposedBy: string
  coverVariant?: 'default' | 'accent' | 'blue'
}

export type ArchiveBookGenre = 'fiction' | 'nonfiction' | 'scifi'

export type ArchiveBookReview = {
  memberName: string
  memberInitials: string
  rating: number
  text: string
}

export type ArchiveDiscussionMessage = {
  memberName: string
  memberInitials: string
  dateLabel: string
  text: string
}

export type ArchiveBook = {
  slug: string
  title: string
  coverTitle: string
  author: string
  genre: ArchiveBookGenre
  genreLabel: string
  cycleNumber: number
  cycleLabel: string
  completedLabel: string
  proposedBy: string
  proposerInitials: string
  rating: number
  synopsis: string
  meetingLabel: string
  discussionPrompt: string
  coverColor: string
  reviews: ArchiveBookReview[]
  discussion: ArchiveDiscussionMessage[]
}
