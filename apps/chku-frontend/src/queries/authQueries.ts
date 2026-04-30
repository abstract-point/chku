import { computed, type Ref } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
import { authApi } from '@/api/endpoints/auth'
import type { ApiAuthUser } from '@/api/types'
import { queryClient } from '@/queries/client'
import { queryKeys } from '@/queries/keys'

export function authSessionQueryOptions() {
  return {
    queryKey: queryKeys.authSession,
    queryFn: () => authApi.me(),
    staleTime: 60_000,
  }
}

export async function fetchAuthSession(): Promise<ApiAuthUser> {
  return queryClient.fetchQuery(authSessionQueryOptions())
}

export function getCachedAuthSession(): ApiAuthUser | undefined {
  return queryClient.getQueryData<ApiAuthUser>(queryKeys.authSession)
}

function refreshAuthSession(client: ReturnType<typeof useQueryClient>) {
  client.invalidateQueries({ queryKey: queryKeys.authSession })
}

function clearTwoFactorSetupQueries(client: ReturnType<typeof useQueryClient>) {
  client.removeQueries({ queryKey: queryKeys.twoFactorQrCode })
  client.removeQueries({ queryKey: queryKeys.twoFactorSecretKey })
  client.removeQueries({ queryKey: queryKeys.twoFactorRecoveryCodes })
}

export function useAuthSessionQuery(options: { enabled?: Ref<boolean> } = {}) {
  return useQuery({
    ...authSessionQueryOptions(),
    enabled: options.enabled,
  })
}

export function useAuthSession(options: { enabled?: Ref<boolean> } = {}) {
  const sessionQuery = useAuthSessionQuery(options)
  const session = computed(() => sessionQuery.data.value ?? null)
  const user = computed(() => session.value?.user ?? null)
  const roles = computed(() => session.value?.roles ?? [])
  const permissions = computed(() => session.value?.permissions ?? [])
  const isAuthenticated = computed(() => session.value !== null)
  const isAdmin = computed(() => roles.value.includes('admin') || roles.value.includes('developer'))
  const isDeveloper = computed(() => roles.value.includes('developer'))
  const twoFactorEnabled = computed(() => session.value?.twoFactorEnabled ?? false)

  return {
    sessionQuery,
    session,
    user,
    roles,
    permissions,
    isAuthenticated,
    isAdmin,
    isDeveloper,
    twoFactorEnabled,
  }
}

export function useLoginMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: async (payload: { email: string; password: string; remember: boolean }) => {
      const response = await authApi.login(payload.email, payload.password, payload.remember)
      if (response.two_factor_required) {
        return { twoFactorRequired: true as const }
      }

      client.setQueryData(queryKeys.authSession, response)
      return { twoFactorRequired: false as const }
    },
  })
}

export function useTwoFactorChallengeMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: async (code: string) => {
      await authApi.twoFactorChallenge(code)
      return authApi.me()
    },
    onSuccess: (session) => {
      client.setQueryData(queryKeys.authSession, session)
    },
  })
}

export function useLogoutMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: () => authApi.logout(),
    onSettled: () => {
      client.clear()
    },
  })
}

export function useUpdateProfileMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: authApi.updateProfile,
    onSuccess: () => refreshAuthSession(client),
  })
}

export function useUpdatePasswordMutation() {
  return useMutation({
    mutationFn: authApi.updatePassword,
  })
}

export function useEnableTwoFactorMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: () => authApi.enableTwoFactor(),
    onSuccess: () => clearTwoFactorSetupQueries(client),
  })
}

export function useConfirmTwoFactorSetupMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: (code: string) => authApi.confirmTwoFactorSetup(code),
    onSuccess: () => {
      refreshAuthSession(client)
      clearTwoFactorSetupQueries(client)
    },
  })
}

export function useDisableTwoFactorMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: () => authApi.disableTwoFactor(),
    onSuccess: () => {
      refreshAuthSession(client)
      clearTwoFactorSetupQueries(client)
    },
  })
}

export function useTwoFactorQrCodeQuery(enabled: Ref<boolean>) {
  return useQuery({
    queryKey: queryKeys.twoFactorQrCode,
    queryFn: () => authApi.twoFactorQrCode(),
    enabled,
    staleTime: 0,
  })
}

export function useTwoFactorSecretKeyQuery(enabled: Ref<boolean>) {
  return useQuery({
    queryKey: queryKeys.twoFactorSecretKey,
    queryFn: () => authApi.twoFactorSecretKey(),
    enabled,
    staleTime: 0,
  })
}

export function useTwoFactorRecoveryCodesQuery(enabled: Ref<boolean>) {
  return useQuery({
    queryKey: queryKeys.twoFactorRecoveryCodes,
    queryFn: () => authApi.twoFactorRecoveryCodes(),
    enabled,
    retry: false,
    staleTime: 0,
  })
}

export function useRegenerateTwoFactorRecoveryCodesMutation() {
  const client = useQueryClient()

  return useMutation({
    mutationFn: () => authApi.regenerateTwoFactorRecoveryCodes(),
    onSuccess: () => {
      client.invalidateQueries({ queryKey: queryKeys.twoFactorRecoveryCodes })
    },
  })
}
