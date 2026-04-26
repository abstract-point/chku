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
    description: 'Роман о закрытом круге студентов, античной культуре и последствиях одного решения.',
  },
  proposer: {
    ...members[0]!,
    name: 'Елена',
  },
  reason: 'Подойдёт для обсуждения ответственности, дружбы и того, как интеллектуальная среда меняет людей.',
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
    mutate: vi.fn((_payload, options?: { onSuccess?: () => void }) => {
      options?.onSuccess?.()
    }),
  }
}

vi.mock('@/queries/dashboardQueries', () => ({
  useDashboardQuery: () => queryResult(dashboard),
}))

vi.mock('@/queries/memberQueries', () => ({
  useCurrentUserQuery: () => queryResult(members[0]),
  useMembersQuery: () => queryResult(members),
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
    const data = computed(() => (String(toValue(id)) === meetingDetail.id ? meetingDetail : undefined))
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
