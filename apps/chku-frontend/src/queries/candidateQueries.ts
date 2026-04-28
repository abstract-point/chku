import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { candidatesApi } from '@/api/endpoints/candidates'
import type { ApiCandidateResponseValue } from '@/api/types'
import { queryKeys } from '@/queries/keys'
import type { BookProposalForm } from '@/types/club'

function invalidateCandidateState(queryClient: ReturnType<typeof useQueryClient>) {
  queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
  queryClient.invalidateQueries({ queryKey: queryKeys.activeCandidate })
}

export function useActiveCandidateQuery() {
  return useQuery({
    queryKey: queryKeys.activeCandidate,
    queryFn: () => candidatesApi.active(),
    staleTime: 30_000,
  })
}

export function useCreateCandidateMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (form: BookProposalForm) => candidatesApi.create(form),
    onSuccess: () => invalidateCandidateState(queryClient),
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
    onSuccess: () => invalidateCandidateState(queryClient),
  })
}

export function useApproveCandidateMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (candidateId: number) => candidatesApi.approve(candidateId),
    onSuccess: () => invalidateCandidateState(queryClient),
  })
}
