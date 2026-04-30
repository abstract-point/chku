import { computed, ref, toValue } from 'vue'
import { vi } from 'vitest'
import { archiveBooks } from '@/data/club/archiveBooks'
import { currentBook, memberProgress, nextMeeting, turnOrder } from '@/data/dashboard'
import { meetingDetail } from '@/data/meetings/meetingDetail'
import { members } from '@/data/members'

const activeCandidate = {
  id: 1,
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
  responses: [],
  createdAt: '2023-10-01T00:00:00.000000Z',
}

const dashboard = {
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
  useDashboardQuery: () => queryResult(dashboard),
  useUpdateReadingProgressMutation: () => mutationResult(),
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
  useMeetingQuery: (id: unknown) => {
    const data = computed(() =>
      String(toValue(id)) === meetingDetail.id ? meetingDetail : undefined,
    )
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
    }
  },
}))

vi.mock('@/queries/candidateQueries', () => ({
  useActiveCandidateQuery: () => queryResult(activeCandidate),
  useCreateCandidateMutation: () => mutationResult(),
  useCandidateResponseMutation: () => mutationResult(),
  useApproveCandidateMutation: () => mutationResult(),
}))
