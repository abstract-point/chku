import { useQuery } from '@tanstack/vue-query'
import { dashboardApi } from '@/api/endpoints/dashboard'
import { mapDashboard } from '@/mappers/dashboardMapper'
import { queryKeys } from '@/queries/keys'

export function useDashboardQuery() {
  return useQuery({
    queryKey: queryKeys.dashboard,
    queryFn: async () => mapDashboard(await dashboardApi.get()),
    staleTime: 30_000,
  })
}
