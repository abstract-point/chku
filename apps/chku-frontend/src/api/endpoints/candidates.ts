import { http } from '@/api/http'
import type { ApiBookCandidate, ApiCandidateResponseValue } from '@/api/types'
import type { BookProposalForm } from '@/types/club'

export const candidatesApi = {
  async active() {
    return http.get<unknown, ApiBookCandidate | null>('/candidates/active')
  },

  async create(form: BookProposalForm) {
    return http.post<unknown, ApiBookCandidate>('/candidates', form)
  },

  async respond(candidateId: number, response: ApiCandidateResponseValue) {
    return http.patch<unknown, ApiBookCandidate>(`/candidates/${candidateId}/responses/me`, {
      response,
    })
  },

  async approve(candidateId: number) {
    return http.post<unknown, ApiBookCandidate>(`/candidates/${candidateId}/approve`)
  },
}

