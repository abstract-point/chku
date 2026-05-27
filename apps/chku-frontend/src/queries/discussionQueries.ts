import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { discussionApi } from '@/api/endpoints/discussion'
import { queryKeys } from '@/queries/keys'

export function useCreateDiscussionMessageMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: ({ cycleNumber, text, parentId }: { cycleNumber: number; text: string; parentId?: number | null }) =>
      discussionApi.create(cycleNumber, { text, parentId }),
    onSuccess: (_data, variables) => {
      client.invalidateQueries({ queryKey: queryKeys.cycle(variables.cycleNumber) })
    },
  })
}
