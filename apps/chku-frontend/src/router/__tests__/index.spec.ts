import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { members } from '@/data/members'
import { useAuthStore } from '@/stores/auth'

import router from '../index'

describe('router', () => {
  beforeEach(async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const auth = useAuthStore()
    auth.authData = {
      user: members[0]!,
      roles: ['member'],
      permissions: [],
      twoFactorEnabled: false,
    }
    await router.push('/')
    await router.isReady()
  })

  it('registers proposal route', async () => {
    await router.push('/propose-selection')

    expect(router.currentRoute.value.name).toBe('propose-selection')
  })

  it('registers archive routes', async () => {
    await router.push('/archive')
    expect(router.currentRoute.value.name).toBe('archive')

    await router.push('/archive/duna')
    expect(router.currentRoute.value.name).toBe('archive-book')
  })
})
