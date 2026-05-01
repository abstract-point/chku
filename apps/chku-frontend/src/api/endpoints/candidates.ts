import { http } from '@/api/http'
import type { ApiBookCandidate, ApiCandidateResponseValue } from '@/api/types'

export const candidatesApi = {
  async active() {
    return http.get<unknown, ApiBookCandidate | null>('/candidates/active')
  },

  async respond(candidateId: number, response: ApiCandidateResponseValue) {
    return http.patch<unknown, ApiBookCandidate>(`/candidates/${candidateId}/responses/me`, {
      response,
    })
  },

  async confirm(candidateId: number) {
    return http.post<unknown, ApiBookCandidate>(`/candidates/${candidateId}/confirm`)
  },
}
