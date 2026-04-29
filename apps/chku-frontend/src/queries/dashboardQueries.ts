import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { dashboardApi } from '@/api/endpoints/dashboard'
import { readingProgressApi, type ReadingProgressPayload } from '@/api/endpoints/readingProgress'
import { mapDashboard } from '@/mappers/dashboardMapper'
import { queryKeys } from '@/queries/keys'

export function useDashboardQuery() {
  return useQuery({
    queryKey: queryKeys.dashboard,
    queryFn: async () => mapDashboard(await dashboardApi.get()),
    staleTime: 30_000,
  })
}

export function useUpdateReadingProgressMutation() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (payload: ReadingProgressPayload) => readingProgressApi.updateCurrent(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.dashboard })
    },
  })
}
