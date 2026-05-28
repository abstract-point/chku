import { computed, ref, toValue } from 'vue'
import { beforeEach, vi } from 'vitest'
import { config } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import ru from '@/i18n/ru'

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  fallbackLocale: 'ru',
  messages: { ru },
})

config.global.plugins = [i18n]

beforeEach(() => {
  if (!document.getElementById('modal-portal')) {
    const portal = document.createElement('div')
    portal.id = 'modal-portal'
    document.body.appendChild(portal)
  }
})
import { archiveBooks } from '@/data/club/archiveBooks'
import { currentBook, memberProgress, nextMeeting, turnOrder } from '@/data/dashboard'
import { meetingDetail as baseMeetingDetail } from '@/data/meetings/meetingDetail'
import type { MeetingDetail } from '@/types/dashboard'

const meetingDetail = ref<MeetingDetail>({ ...baseMeetingDetail })

export function patchMeetingDetail(partial: Partial<MeetingDetail>) {
  meetingDetail.value = { ...meetingDetail.value, ...partial }
}

export function resetMeetingDetail() {
  meetingDetail.value = { ...baseMeetingDetail }
}
import { members } from '@/data/members'

export const activeCandidate = {
  id: 1,
  queueItemId: 1,
  cycleNumber: 42,
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
  canEditBook: false,
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
    refetch: vi.fn<() => void>(),
  }
}

function mutationResult() {
  return {
    isPending: ref(false),
    error: ref(null),
    reset: vi.fn<() => void>(),
    mutate: vi.fn<(_payload: unknown, options?: { onSuccess?: () => void }) => void>(
      (_payload, options) => {
        options?.onSuccess?.()
      },
    ),
    mutateAsync: vi.fn<() => Promise<undefined>>(async () => undefined),
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
  fetchAuthSession: vi.fn<() => Promise<typeof authSession.value>>(async () => authSession.value),
  getCachedAuthSession: vi.fn<() => typeof authSession.value>(() => authSession.value),
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
        authSession.value.roles.includes('admin') || authSession.value.roles.includes('developer'),
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
  useClubQuery: () =>
    queryResult({
      id: 1,
      name: 'Читальный клуб умничек',
      shortName: 'ЧКУ',
    }),
}))

vi.mock('@/queries/genreQueries', () => ({
  useGenresQuery: () =>
    queryResult([
      { id: 1, slug: 'classic', name: 'Классика' },
      { id: 2, slug: 'science_fiction', name: 'Научная фантастика' },
      { id: 3, slug: 'history', name: 'История' },
    ]),
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

vi.mock('@/queries/cycleQueries', () => ({
  useCyclesQuery: () =>
    queryResult([
      {
        ...archiveBooks[0]!,
        id: 42,
        cycleNumber: 42,
        cycleLabel: 'Цикл #42',
        status: 'active' as const,
        statusLabel: 'Читаем сейчас',
        completedLabel: null,
        book: {
          ...archiveBooks[0]!.book,
          title: 'Цветы для Элджернона',
          author: 'Дэниел Киз',
        },
      },
      ...archiveBooks,
    ]),
  useCycleQuery: (cycleNumber: unknown) => {
    const data = computed(() =>
      archiveBooks.find((cycle) => cycle.cycleNumber === Number(toValue(cycleNumber))),
    )
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
    }
  },
  useUpdateCycleBookMutation: () => mutationResult(),
}))

vi.mock('@/queries/meetingQueries', () => ({
  useMeetingQuery: (_id: unknown, _currentUserId?: unknown) => {
    const data = computed(() =>
      String(toValue(_id)) === meetingDetail.value.id ? meetingDetail.value : undefined,
    )
    const error = computed(() => (data.value ? null : new Error('Not found')))

    return {
      data,
      isLoading: ref(false),
      error,
      refetch: vi.fn<() => void>(),
    }
  },
  useUpdateMeetingRsvpMutation: () => mutationResult(),
  useRemoveMeetingRsvpMutation: () => mutationResult(),
  useAddMeetingTopicMutation: () => mutationResult(),
  useCreateMeetingMutation: () => mutationResult(),
  useUpdateMeetingMutation: () => mutationResult(),
  useStartMeetingMutation: () => mutationResult(),
  useFinishMeetingMutation: () => mutationResult(),
}))

vi.mock('@/queries/discussionQueries', () => ({
  useCreateDiscussionMessageMutation: () => mutationResult(),
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
        nextQueueItemId: 2,
        isHead: true,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Шум времени',
        author: 'Джулиан Барнс',
        description: 'Документальный роман о выборе под давлением.',
      },
      {
        id: 2,
        nextQueueItemId: null,
        isHead: false,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Лавр',
        author: 'Евгений Водолазкин',
      },
    ]),
    items: computed(() => [
      {
        id: 1,
        nextQueueItemId: 2,
        isHead: true,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Шум времени',
        author: 'Джулиан Барнс',
        description: 'Документальный роман о выборе под давлением.',
      },
      {
        id: 2,
        nextQueueItemId: null,
        isHead: false,
        isCurrentCandidate: false,
        canBecomeCandidate: true,
        status: 'queued',
        title: 'Лавр',
        author: 'Евгений Водолазкин',
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
