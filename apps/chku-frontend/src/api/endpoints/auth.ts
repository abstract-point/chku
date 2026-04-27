import axios from 'axios'
import { fetchCsrfCookie, http } from '@/api/http'
import type { ApiAuthUser } from '@/api/types'

const fortifyHttp = axios.create({
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

export const authApi = {
  async login(email: string, password: string) {
    await fetchCsrfCookie()
    return http.post<unknown, { two_factor_required?: boolean; user?: ApiAuthUser }>('/login', {
      email,
      password,
    })
  },

  async twoFactorChallenge(code: string) {
    await fetchCsrfCookie()
    return fortifyHttp.post<unknown, { user?: ApiAuthUser }>('/two-factor-challenge', {
      code,
    })
  },

  async logout() {
    return http.post<unknown, { message: string }>('/logout')
  },

  async me() {
    return http.get<unknown, ApiAuthUser>('/me')
  },
}
