import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import router from '../index'
import { useClubStore } from '@/stores/club'

describe('router', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await router.push('/')
    await router.isReady()
  })

  it('redirects proposal route to profile when it is not the member turn', async () => {
    const club = useClubStore()
    club.currentSelectorName = 'Михаил'

    await router.push('/propose-selection')

    expect(router.currentRoute.value.name).toBe('profile')
  })

  it('allows proposal route for the current selector', async () => {
    const club = useClubStore()
    club.currentSelectorName = club.currentUserName

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
