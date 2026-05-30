export type BookChoiceEvent = {
  id: number
  type: 'book-choice'
  proposerName: string
  bookTitle: string
  author: string
  description: string
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
  coverColor?: string | null
  description?: string | null
  genres?: { id: number; slug: string; name: string }[]
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
  createdAt?: string
  isActive: boolean
  email: string
  favoriteGenres: { id: number; slug: string; name: string }[]
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
  description?: string | null
  genres?: { id: number; slug: string; name: string }[]
  coverColor?: string | null
  coverUrl?: string | null
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

export type ArchiveBookGenre = string

export type ArchiveBookReview = {
  memberId?: number
  memberName: string
  memberAvatarUrl?: string | null
  rating: number
  text: string
}

export type ArchiveDiscussionMessage = {
  id?: number
  memberId?: number
  memberName: string
  memberAvatarUrl?: string | null
  dateLabel?: string
  text: string
  createdAt?: string
  parentId?: number | null
  canReply?: boolean
  replies?: ArchiveDiscussionMessage[]
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
    genres?: { id: number; slug: string; name: string }[]
  }
  coverTitle: string
  genre: string | null
  genreLabel: string | null
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
  reviews: ArchiveBookReview[]
  discussion: ArchiveDiscussionMessage[]
}
