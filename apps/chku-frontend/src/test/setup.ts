import { computed, ref, toValue } from 'vue'
import { vi } from 'vitest'
import { archiveBooks } from '@/data/club/archiveBooks'
import { currentBook, memberProgress, nextMeeting, turnOrder } from '@/data/dashboard'
import { meetingDetail } from '@/data/meetings/meetingDetail'
import { members } from '@/data/members'

export const activeCandidate = {
  id: 1,
  queueItemId: 1,
  book: {
    id: 1,
    slug: 'taynaya-istoriya',
    title: 'Тайную историю',
    author: 'Донна Тартт',
    description:
      'Роман о закрытом круге студентов, античной культуре и последствиях одного решения.',
  },
  proposer: {
    ...members[0]!,
    name: 'Елена',
  },
  reason:
    'Подойдёт для обсуждения ответственности, дружбы и того, как интеллектуальная среда меняет людей.',
  description: 'Роман о закрытом круге студентов, античной культуре и последствиях одного решения.',
  status: 'pending' as const,
  responses: [
    {
      id: 1,
      member: members[0]!,
      response: 'pending' as const,
    },
    {
      id: 2,
      member: members[1]!,
      response: 'not_read' as const,
    },
    {
      id: 3,
      member: members[2]!,
      response: 'pending' as const,
    },
  ],
  canConfirm: false,
  createdAt: '2023-10-01T00:00:00.000000Z',
}

const baseDashboard = {
  club: {
    id: 1,
    name: 'Читальный клуб умничек',
    shortName: 'ЧКУ',
  },
  currentBook,
  memberProgress,
  nextMeeting,
  turnOrder,
  activeCandidate,
  clubStats: [
    { value: '41', label: 'Прочитано книг' },
    { value: '4.8', label: 'Средний рейтинг' },
    { value: '6', label: 'Участников' },
    { value: '12', label: 'Встреч в год' },
  ],
}

const dashboard = ref(baseDashboard)

export function setDashboardMock(nextDashboard: typeof baseDashboard) {
  dashboard.value = nextDashboard
}

export function patchDashboardMock(partialDashboard: Record<string, unknown>) {
  dashboard.value = {
    ...dashboard.value,
    ...partialDashboard,
  } as typeof baseDashboard
}

export function resetDashboardMock() {
  dashboard.value = baseDashboard
}

function queryResult<T>(data: T) {
  return {
    data: ref(data),
    isLoading: ref(false),
    isFetching: ref(false),
    error: ref(null),
    refetch: vi.fn(),
  }
}

function mutationResult() {
  return {
    isPending: ref(false),
    error: ref(null),
    reset: vi.fn(),
    mutate: vi.fn((_payload, options?: { onSuccess?: () => void }) => {
      options?.onSuccess?.()
    }),
    mutateAsync: vi.fn(async () => undefined),
  }
}

const authSession = ref({
  user: members[0]!,
  roles: ['member'],
  permissions: [],
  twoFactorEnabled: false,
})

export function setAuthRoles(roles: string[]) {
  authSession.value = {
    ...authSession.value,
    roles,
  }
}

export function resetAuthSession() {
  authSession.value = {
    user: members[0]!,
    roles: ['member'],
    permissions: [],
    twoFactorEnabled: false,
  }
}

vi.mock('@/queries/authQueries', () => ({
  authSessionQueryOptions: () => ({
    queryKey: ['session', 'me'],
    queryFn: async () => authSession.value,
    staleTime: 60_000,
  }),
  fetchAuthSession: vi.fn(async () => authSession.value),
  getCachedAuthSession: vi.fn(() => authSession.value),
  useAuthSessionQuery: () => queryResult(authSession.value),
  useAuthSession: () => ({
    sessionQuery: queryResult(authSession.value),
    session: authSession,
    user: computed(() => authSession.value.user),
    roles: computed(() => authSession.value.roles),
    permissions: computed(() => authSession.value.permissions),
    isAuthenticated: computed(() => authSession.value !== null),
    isAdmin: computed(
      () =>
        authSession.value.roles.includes('admin') ||
        authSession.value.roles.includes('developer'),
    ),
    isDeveloper: computed(() => authSession.value.roles.includes('developer')),
    twoFactorEnabled: computed(() => authSession.value.twoFactorEnabled ?? false),
  }),
  useLoginMutation: () => mutationResult(),
  useTwoFactorChallengeMutation: () => mutationResult(),
  useLogoutMutation: () => mutationResult(),
  useUpdateProfileMutation: () => mutationResult(),
  useUpdateAvatarMutation: () => mutationResult(),
  useUpdatePasswordMutation: () => mutationResult(),
  useEnableTwoFactorMutation: () => mutationResult(),
  useConfirmTwoFactorSetupMutation: () => mutationResult(),
  useDisableTwoFactorMutation: () => mutationResult(),
  useRegenerateTwoFactorRecoveryCodesMutation: () => mutationResult(),
  useTwoFactorQrCodeQuery: () => queryResult({ svg: '', url: '' }),
  useTwoFactorSecretKeyQuery: () => queryResult({ secretKey: '' }),
  useTwoFactorRecoveryCodesQuery: () => queryResult([]),
}))

vi.mock('@/queries/dashboardQueries', () => ({
  useDashboardQuery: () => queryResult(dashboard.value),
  useUpdateReadingProgressMutation: () => mutationResult(),
}))

vi.mock('@/queries/clubQueries', () => ({
  useClubQuery: () => queryResult({
    id: 1,
    name: 'Читальный клуб умничек',
    shortName: 'ЧКУ',
  }),
}))

vi.mock('@/queries/memberQueries', () => ({
  useCurrentUserQuery: () => queryResult(members[0]),
  useMembersQuery: () => queryResult(members),
  useCreateMemberMutation: () => mutationResult(),
  useDeactivateMemberMutation: () => mutationResult(),
  useMemberQuery: (id: unknown) => {
    const data = computed(() => members.find((member) => member.id === Number(toValue(id))))
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
    }
  },
}))

vi.mock('@/queries/profileQueries', () => ({
  useCurrentUserReadingHistoryQuery: () => queryResult(members[0]!.readingHistory),
}))

vi.mock('@/queries/archiveQueries', () => ({
  useArchiveQuery: () => queryResult(archiveBooks),
  useArchiveBookQuery: (slug: unknown) => {
    const data = computed(() => archiveBooks.find((book) => book.slug === String(toValue(slug))))
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
    }
  },
}))

vi.mock('@/queries/meetingQueries', () => ({
  useMeetingQuery: (_id: unknown, _currentUserId?: unknown) => {
    const data = computed(() =>
      String(toValue(_id)) === meetingDetail.id ? meetingDetail : undefined,
    )
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
    }
  },
  useUpdateMeetingRsvpMutation: () => mutationResult(),
  useAddMeetingTopicMutation: () => mutationResult(),
  useCreateMeetingMutation: () => mutationResult(),
  useUpdateMeetingMutation: () => mutationResult(),
  useStartMeetingMutation: () => mutationResult(),
  useFinishMeetingMutation: () => mutationResult(),
}))

vi.mock('@/queries/candidateQueries', () => ({
  useActiveCandidateQuery: () => queryResult(activeCandidate),
  useCandidateResponseMutation: () => mutationResult(),
  useConfirmCandidateMutation: () => mutationResult(),
}))

vi.mock('@/queries/bookQueueQueries', () => ({
  useBookQueueQuery: () => ({
    ...queryResult([
      {
        id: 1,
        nextQueueItemId: null,
        isHead: true,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Шум времени',
        author: 'Джулиан Барнс',
        reason: 'Роман о компромиссе и достоинстве в эпоху террора.',
      },
    ]),
    items: computed(() => [
      {
        id: 1,
        nextQueueItemId: null,
        isHead: true,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Шум времени',
        author: 'Джулиан Барнс',
        reason: 'Роман о компромиссе и достоинстве в эпоху террора.',
      },
    ]),
  }),
  useRejectedBookQueueQuery: () => ({
    ...queryResult([]),
    items: computed(() => []),
  }),
  useCreateBookQueueItemMutation: () => mutationResult(),
  useRemoveBookQueueItemMutation: () => mutationResult(),
  useUpdateBookQueueItemMutation: () => mutationResult(),
  useMakeBookQueueItemCandidateMutation: () => mutationResult(),
  useReorderBookQueueMutation: () => mutationResult(),
}))

vi.mock('@/queries/readingCycleQueries', () => ({
  useSaveRatingReviewMutation: () => ({
    ...mutationResult(),
    isSuccess: ref(false),
  }),
  useCompleteCurrentCycleMutation: () => mutationResult(),
}))
