export type BookChoiceEvent = {
  type: 'book-choice'
  proposerName: string
  bookTitle: string
  author: string
  description: string
  reason: string
}

export type BookProposalForm = {
  title: string
  author: string
  description: string
  reason: string
}

export type MemberProfile = {
  name: string
  initials: string
  memberSince: string
  email: string
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
