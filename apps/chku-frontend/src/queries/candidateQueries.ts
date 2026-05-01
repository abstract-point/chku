import type { Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { candidatesApi } from '@/api/endpoints/candidates'
import type { ApiBookCandidate, ApiCandidateResponseValue } from '@/api/types'
import { queryKeys } from '@/queries/keys'

function invalidateCandidateState(queryClient: ReturnType<typeof useQueryClient>) {
  queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
  queryClient.invalidateQueries({ queryKey: queryKeys.activeCandidate })
}

export function useActiveCandidateQuery(options: { enabled?: Ref<boolean> } = {}) {
  return useQuery({
    queryKey: queryKeys.activeCandidate,
    queryFn: () => candidatesApi.active(),
    enabled: options.enabled,
    staleTime: 30_000,
  })
}

export function useCandidateResponseMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({
      candidateId,
      response,
    }: {
      candidateId: number
      response: ApiCandidateResponseValue
    }) => candidatesApi.respond(candidateId, response),
    onSuccess: (candidate: ApiBookCandidate) => {
      queryClient.setQueryData(queryKeys.activeCandidate, candidate)
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
    },
  })
}

export function useConfirmCandidateMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (candidateId: number) => candidatesApi.confirm(candidateId),
    onSuccess: () => invalidateCandidateState(queryClient),
  })
}
