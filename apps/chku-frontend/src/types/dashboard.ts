export type CurrentBook = {
  title: string
  coverTitle: string
  author: string
  selectedBy: string
  description: string
  coverColor?: string | null
  coverUrl?: string | null
  genres?: {
    id: number
    slug: string
    name: string
  }[]
  canEditBook: boolean
  progress: number
  progressLabel: string
  cycleNumber: number
  cycleStatus: string
}

export type BookProgressMember = {
  id: number
  name: string
  avatarUrl?: string | null
  status?: string
  progress?: number
  badge?: string
  rank?: number
  medal?: 'gold' | 'silver' | 'bronze' | null
  finishedAt?: string | null
}

export type TurnOrderMember = {
  memberId: number
  name: string
  avatarUrl?: string | null
  status?: string
  active?: boolean
  isNextSelector?: boolean
  isChoosingNow?: boolean
  isCurrentCycleProposer?: boolean
  hasProposedBook?: boolean
  cycleNumber?: number | null
}

export type MeetingSummary = {
  id: string
  title: string
  dateLabel: string
  dayTimeLabel?: string
  date?: string
  time?: string
  place?: string
  isOnline: boolean
  link?: string
  status: 'scheduled' | 'started' | 'finished'
  canStart: boolean
  canFinish: boolean
  isMeetingTime: boolean
  attendees: MeetingAttendee[]
}

export type MeetingAttendee = {
  id: number
  name: string
  avatarUrl?: string | null
  status: 'attending' | 'not_attending' | 'pending'
  favoriteGenres?: { id: number; slug: string; name: string }[]
  memberSince?: string
  isAdmin?: boolean
  isActive?: boolean
}

export type MeetingBook = {
  title: string
  author: string
  cycleSlug?: string
}

export type MeetingDetail = {
  id: string
  title: string
  cycleLabel: string
  cycleId: number
  dateLabel: string
  timeLabel: string
  date?: string
  time?: string
  place?: string
  isOnline: boolean
  placeAddress?: string
  placeReservation?: string
  meetingLink?: string
  discussion?: DiscussionMessage[]
  status: 'scheduled' | 'started' | 'finished'
  canStart: boolean
  canFinish: boolean
  isMeetingTime: boolean
  missingRatingMemberIds: number[]
  missingReadingMemberIds: number[]
  deactivatedAttendeeCount: number
  rsvpStatus: 'attending' | 'not_attending' | 'pending'
  attendees: MeetingAttendee[]
  book: MeetingBook
  ratings: { memberId: number; value: number }[]
  reviews: { memberId: number; text: string }[]
}

export type DiscussionMessage = {
  id: number
  memberId?: number
  memberName: string
  memberAvatarUrl?: string | null
  text: string
  createdAt: string
  parentId: number | null
  canReply: boolean
  replies: DiscussionMessage[]
}
