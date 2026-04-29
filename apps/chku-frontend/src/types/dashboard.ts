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
  id: string
  dateLabel: string
  dayTimeLabel?: string
  place: string
  participantInitials: string[]
  extraParticipantsCount: number
}

export type MeetingAttendee = {
  name: string
  initials: string
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
  dateLabel: string
  timeLabel: string
  place: string
  placeAddress?: string
  placeReservation?: string
  meetingLink?: string
  topics: string[]
  rsvpStatus: 'attending' | 'not_attending' | 'pending'
  attendees: MeetingAttendee[]
  book: MeetingBook
}
