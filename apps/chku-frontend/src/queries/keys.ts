export const queryKeys = {
  dashboard: ['dashboard'] as const,
  activeCandidate: ['candidate', 'active'] as const,
  currentUser: ['session', 'me'] as const,
  members: ['members'] as const,
  member: (id: number) => ['members', id] as const,
  archive: ['archive'] as const,
  archiveBook: (slug: string) => ['archive', slug] as const,
  meeting: (id: string | number) => ['meetings', String(id)] as const,
}

