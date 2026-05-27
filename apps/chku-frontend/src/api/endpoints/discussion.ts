import { http } from '@/api/http'
import type { ApiDiscussionMessage } from '@/api/types'

export const discussionApi = {
  async create(cycleNumber: number, payload: { text: string; parentId?: number | null }) {
    return http.post<unknown, { data: ApiDiscussionMessage }>(
      `/cycles/${cycleNumber}/discussion-messages`,
      payload,
    )
  },
}
