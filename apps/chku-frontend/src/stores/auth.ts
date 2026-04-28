import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { authApi } from '@/api/endpoints/auth'
import type { ApiAuthUser, ApiMember } from '@/api/types'

export const useAuthStore = defineStore('auth', () => {
  const authData = ref<ApiAuthUser | null>(null)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => authData.value !== null)
  const user = computed<ApiMember | null>(() => authData.value?.user ?? null)
  const roles = computed<string[]>(() => authData.value?.roles ?? [])
  const permissions = computed<string[]>(() => authData.value?.permissions ?? [])
  const isAdmin = computed(() => roles.value.includes('admin') || roles.value.includes('developer'))
  const isDeveloper = computed(() => roles.value.includes('developer'))
  const twoFactorEnabled = computed(() => authData.value?.twoFactorEnabled ?? false)

  async function login(email: string, password: string): Promise<{ twoFactorRequired: boolean }> {
    isLoading.value = true
    try {
      const response = await authApi.login(email, password)
      if (response.two_factor_required) {
        return { twoFactorRequired: true }
      }
      authData.value = response
      return { twoFactorRequired: false }
    } finally {
      isLoading.value = false
    }
  }

  async function confirmTwoFactor(code: string): Promise<void> {
    isLoading.value = true
    try {
      await authApi.twoFactorChallenge(code)
      authData.value = await authApi.me()
    } finally {
      isLoading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } finally {
      authData.value = null
    }
  }

  async function fetchUser(): Promise<boolean> {
    try {
      const data = await authApi.me()
      authData.value = data
      return true
    } catch {
      authData.value = null
      return false
    }
  }

  return {
    authData,
    isLoading,
    isAuthenticated,
    user,
    roles,
    permissions,
    isAdmin,
    isDeveloper,
    twoFactorEnabled,
    login,
    confirmTwoFactor,
    logout,
    fetchUser,
  }
})
