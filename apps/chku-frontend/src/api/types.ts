export type ApiMember = {
  id: number
  name: string
  avatarUrl?: string | null
  email: string
  isActive: boolean
  memberSince: string
  favoriteGenreId?: number | null
  favoriteGenre: string
  isAdmin?: boolean
  stats?: {
    read: number
    proposed: number
    meetings: number
    goldOwls: number
    silverOwls: number
    bronzeOwls: number
  }
  readingHistory?: ApiProfileBook[]
}

export type ApiProfileBook = {
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

export type ApiClub = {
  id: number
  name: string
  shortName: string
}

export type ApiBook = {
  id: number
  slug: string
  title: string
  author: string
  description?: string
  coverColor?: string | null
  coverUrl?: string | null
  genre?: {
    id: number
    slug: string
    name: string
  }
}

export type ApiCandidateResponseValue = 'not_read' | 'read' | 'pending'

export type ApiBookCandidate = {
  id: number
  queueItemId?: number | null
  cycleNumber?: number | null
  book: ApiBook
  proposer: ApiMember
  reason: string
  description: string
  status: 'pending' | 'awaiting_owner_confirmation' | 'approved' | 'rejected'
  responses: ApiBookCandidateResponse[]
  canConfirm?: boolean
  canEditBook?: boolean
  createdAt: string
}

export type ApiBookCandidateResponse = {
  id: number
  member: ApiMember
  response: ApiCandidateResponseValue
}

export type ApiMeeting = {
  id: number
  title: string
  cycleLabel?: string
  cycleId?: number
  date?: string
  time?: string
  place?: string
  address?: string
  reservation?: string
  link?: string
  isOnline?: boolean
  discussion?: ApiDiscussionMessage[]
  notes?: string
  startedAt?: string | null
  finishedAt?: string | null
  status?: 'scheduled' | 'started' | 'finished'
  canStart?: boolean
  canFinish?: boolean
  missingRatingMemberIds?: number[]
  missingReadingMemberIds?: number[]
  rsvps?: ApiMeetingRsvp[]
  reschedules?: ApiMeetingReschedule[]
  book?: ApiBook
  ratings?: { memberId: number; value: number }[]
  reviews?: { memberId: number; text: string }[]
}

export type ApiMeetingRsvp = {
  id: number
  member: ApiMember
  status: 'attending' | 'not_attending' | 'pending'
}

export type ApiMeetingReschedule = {
  id: number
  oldDate?: string
  oldTime?: string
  newDate?: string
  newTime?: string
  reason?: string
  actorName?: string
  createdAt: string
}

export type ApiMemberBookQueueItem = {
  id: number
  nextQueueItemId?: number | null
  isHead?: boolean
  isCurrentCandidate?: boolean
  canBecomeCandidate?: boolean
  status: 'queued' | 'in_verification' | 'approved' | 'rejected' | 'removed'
  reason?: string | null
  description?: string | null
  book: ApiBook
}

export type ApiDashboard = {
  club: ApiClub
  currentBook: {
    title: string
    coverTitle: string
    author: string
    selectedBy: string
    description: string
    coverColor?: string | null
    coverUrl?: string | null
    genre?: ApiBook['genre'] | null
    canEditBook: boolean
    progress: number
    progressLabel: string
    cycleNumber: number
    cycleStatus: string
  } | null
  memberProgress: {
    id: number
    name: string
    avatarUrl?: string | null
    status?: string | null
    progress?: number | null
    badge?: string | null
    rank?: number
    medal?: 'gold' | 'silver' | 'bronze' | null
    finishedAt?: string | null
  }[]
  nextMeeting: ApiMeeting | null
  turnOrder: {
    name: string
    avatarUrl?: string | null
    status?: string
    active?: boolean
    isNextSelector?: boolean
    isChoosingNow?: boolean
    isCurrentCycleProposer?: boolean
    hasProposedBook?: boolean
    cycleNumber?: number | null
  }[]
  activeCandidate: ApiBookCandidate | null
  lifecycle?: {
    state: string
    currentCycleStatus?: string | null
    currentCycleId?: number | null
    currentCycleNumber?: number | null
    nextSelector?: ApiMember | null
    nextSelectorName?: string | null
    nextSelectorQueueEmpty: boolean
    shouldShowChooseBookBanner: boolean
    canCompleteCycle: boolean
    missingRatings: ApiMember[]
  }
  clubStats: {
    value: string
    label: string
  }[]
}

export type ApiReadingProgress = {
  id: number
  member: ApiMember
  status: 'not_started' | 'reading' | 'finished' | 'abandoned'
  progressPercent: number | null
  currentPage: number | null
  notes: string | null
}

export type ApiAuthUser = {
  user: ApiMember | null
  roles: string[]
  permissions: string[]
  twoFactorEnabled?: boolean
}

export type ApiGenre = {
  id: number
  slug: string
  name: string
}

export type ApiCycle = {
  id: number
  cycleNumber: number
  cycleLabel: string
  status: 'proposed' | 'active' | 'completed'
  statusLabel: string
  canEditBook: boolean
  book: ApiBook
  coverTitle: string
  genre: 'fiction' | 'nonfiction' | 'scifi'
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
  meeting?: {
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
  } | null
  candidate?: ApiBookCandidate | null
  memberProgress: ApiReadingProgress[]
  reviews: {
    memberName: string
    memberAvatarUrl?: string | null
    rating: number
    text: string
  }[]
  discussion: ApiDiscussionMessage[]
}

export type ApiDiscussionMessage = {
  id: number
  memberName: string
  memberAvatarUrl?: string | null
  text: string
  createdAt: string
  parentId: number | null
  canReply: boolean
  replies: ApiDiscussionMessage[]
}
