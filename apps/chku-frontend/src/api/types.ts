export type ApiMember = {
  id: number
  name: string
  initials: string
  email: string
  isActive: boolean
  memberSince: string
  favoriteGenre: string
  stats?: {
    read: number
    proposed: number
    meetings: number
  }
  readingHistory?: ApiProfileBook[]
}

export type ApiProfileBook = {
  title: string
  coverTitle: string
  author: string
  completedLabel: string
  proposedBy: string
  coverVariant?: 'default' | 'accent' | 'blue'
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
  coverTitle?: string
  author: string
  description?: string
  genre?: {
    id: number
    slug: string
    name: string
  }
}

export type ApiCandidateResponseValue = 'not_read' | 'read' | 'not_sure' | 'pending'

export type ApiBookCandidate = {
  id: number
  book: ApiBook
  proposer: ApiMember
  reason: string
  description: string
  status: 'pending' | 'approved' | 'rejected'
  responses: ApiBookCandidateResponse[]
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
  date?: string
  time?: string
  place: string
  address?: string
  reservation?: string
  link?: string
  topics?: string[]
  notes?: string
  rsvps?: ApiMeetingRsvp[]
  book?: ApiBook
}

export type ApiMeetingRsvp = {
  id: number
  member: ApiMember
  status: 'attending' | 'not_attending' | 'pending'
}

export type ApiDashboard = {
  club: ApiClub
  currentBook: {
    title: string
    coverTitle: string
    author: string
    selectedBy: string
    description: string
    progress: number
    progressLabel: string
  } | null
  memberProgress: {
    initials: string
    name: string
    status?: string | null
    progress?: number | null
    badge?: string | null
  }[]
  nextMeeting: ApiMeeting | null
  turnOrder: {
    name: string
    status: string
    active?: boolean
  }[]
  activeCandidate: ApiBookCandidate | null
  clubStats: {
    value: string
    label: string
  }[]
}

export type ApiArchiveBook = {
  slug: string
  title: string
  coverTitle: string
  author: string
  genre: 'fiction' | 'nonfiction' | 'scifi'
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
  reviews: {
    memberName: string
    memberInitials: string
    rating: number
    text: string
  }[]
  discussion: {
    memberName: string
    memberInitials: string
    dateLabel: string
    text: string
  }[]
}
