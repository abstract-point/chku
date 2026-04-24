export type CurrentBook = {
  title: string
  coverTitle: string
  author: string
  selectedBy: string
  description: string
  progress: number
  progressLabel: string
}

export type BookProgressMember = {
  initials: string
  name: string
  status?: string
  progress?: number
  badge?: string
}

export type TurnOrderMember = {
  name: string
  status: string
  active?: boolean
}

export type MeetingSummary = {
  dateLabel: string
  place: string
  participantInitials: string[]
  extraParticipantsCount: number
}
