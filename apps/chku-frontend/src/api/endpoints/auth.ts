import { fetchCsrfCookie, http } from '@/api/http'
import type { ApiAuthUser, ApiMember } from '@/api/types'

export const authApi = {
  async login(email: string, password: string, remember = false) {
    await fetchCsrfCookie()
    return http.post<
      unknown,
      (ApiAuthUser & { two_factor_required?: false }) | { two_factor_required: true }
    >('/login', {
      email,
      password,
      remember,
    })
  },

  async twoFactorChallenge(code: string) {
    return http.post<unknown, void>('/two-factor-challenge', {
      code,
    })
  },

  async logout() {
    return http.post<unknown, { message: string }>('/logout')
  },

  async me() {
    return http.get<unknown, ApiAuthUser>('/me')
  },

  async updateProfile(payload: {
    name: string
    initials: string
    favorite_genre_id: number | null
  }) {
    return http.patch<unknown, ApiMember>('/me/profile', payload)
  },

  async updatePassword(payload: {
    current_password: string
    password: string
    password_confirmation: string
  }) {
    return http.put<unknown, { message: string }>('/me/password', payload)
  },

  async enableTwoFactor() {
    return http.post<unknown, void>('/me/two-factor-authentication')
  },

  async confirmTwoFactorSetup(code: string) {
    return http.post<unknown, void>('/me/confirmed-two-factor-authentication', { code })
  },

  async disableTwoFactor() {
    return http.delete<unknown, void>('/me/two-factor-authentication')
  },

  async twoFactorQrCode() {
    return http.get<unknown, { svg: string; url: string }>('/me/two-factor-qr-code')
  },

  async twoFactorSecretKey() {
    return http.get<unknown, { secretKey: string }>('/me/two-factor-secret-key')
  },

  async twoFactorRecoveryCodes() {
    return http.get<unknown, string[]>('/me/two-factor-recovery-codes')
  },

  async regenerateTwoFactorRecoveryCodes() {
    return http.post<unknown, void>('/me/two-factor-recovery-codes')
  },
}
