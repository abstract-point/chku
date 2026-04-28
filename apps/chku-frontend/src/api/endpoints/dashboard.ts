import { http } from '@/api/http'
import type { ApiDashboard } from '@/api/types'

export const dashboardApi = {
  async get() {
    return http.get<unknown, ApiDashboard>('/dashboard')
  },
}
