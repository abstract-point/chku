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
      avatarUrl?: string | null
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
  nextQueueItemId?: number | null
  isHead?: boolean
  isCurrentCandidate?: boolean
  canBecomeCandidate?: boolean
  status: 'queued' | 'in_verification' | 'approved' | 'rejected' | 'removed'
  title: string
  author: string
  coverUrl?: string | null
  description?: string | null
  reason?: string | null
  rejectionInfo?: {
    rejectedAt: string
    rejectedByMembers: string[]
  } | null
}

export type MemberProfile = {
  id: number
  name: string
  avatarUrl?: string | null
  memberSince: string
  isActive: boolean
  email: string
  favoriteGenreId?: number | null
  favoriteGenre: string
  stats: {
    read: number
    proposed: number
    meetings: number
    goldOwls: number
    silverOwls: number
    bronzeOwls: number
  }
  readingHistory: ProfileBook[]
}

export type ProfileBook = {
  title: string
  coverTitle: string
  author: string
  coverColor?: string | null
  cycleNumber: number
  cycleLabel: string
  completedLabel: string
  proposedBy: string
  myRating?: number | null
  clubAverageRating?: number | null
  hasReview: boolean
  meetingRsvpStatus?: 'attending' | 'not_attending' | 'pending' | null
  attendedMeeting: boolean
}

export type ArchiveBookGenre = 'fiction' | 'nonfiction' | 'scifi'

export type ArchiveBookReview = {
  memberName: string
  memberAvatarUrl?: string | null
  rating: number
  text: string
}

export type ArchiveDiscussionMessage = {
  memberName: string
  memberAvatarUrl?: string | null
  dateLabel: string
  text: string
}

export type ArchiveMeeting = {
  id: number
  title: string
  date?: string | null
  time?: string | null
  place?: string | null
  link?: string | null
  isOnline: boolean
  status: 'scheduled' | 'started' | 'finished'
  attendingCount: number
  rsvpCount: number
}

export type ArchiveCycle = {
  id: number
  cycleNumber: number
  cycleLabel: string
  status: 'proposed' | 'active' | 'completed'
  statusLabel: string
  canEditBook: boolean
  book: {
    id: number
    slug: string
    title: string
    author: string
    description?: string | null
    coverColor?: string | null
    coverUrl?: string | null
    genre?: {
      id: number
      slug: string
      name: string
    }
  }
  coverTitle: string
  genre: ArchiveBookGenre
  genreLabel: string
  completedLabel?: string | null
  proposedBy: string
  proposerAvatarUrl?: string | null
  rating: number
  averageRating?: number
  ratingsCount?: number
  reviewsCount?: number
  attendingCount?: number
  rsvpCount?: number
  meetingLabel?: string | null
  meeting?: ArchiveMeeting | null
  candidate?: {
    id: number
    status: 'pending' | 'awaiting_owner_confirmation' | 'approved' | 'rejected'
    reason?: string | null
    description?: string | null
    responses: {
      id: number
      member: {
        id: number
        name: string
        avatarUrl?: string | null
      }
      response: 'not_read' | 'read' | 'pending'
    }[]
  } | null
  memberProgress: {
    id: number
    member: {
      id: number
      name: string
      avatarUrl?: string | null
    }
    status: 'not_started' | 'reading' | 'finished' | 'abandoned'
    progressPercent: number | null
    currentPage: number | null
    notes: string | null
  }[]
  discussionPrompt: string
  reviews: ArchiveBookReview[]
  discussion: ArchiveDiscussionMessage[]
}
