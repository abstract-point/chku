import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import BannerSection from '../BannerSection.vue'
import { patchDashboardMock, resetDashboardMock, resetAuthSession } from '@/test/setup'

const route = ref({
  path: '/meetings/october-42',
  name: 'meeting-detail',
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
}))

describe('BannerSection', () => {
  afterEach(() => {
    resetDashboardMock()
    resetAuthSession()
    route.value = { path: '/meetings/october-42', name: 'meeting-detail' }
  })

  it('shows meeting rating banner on meeting page for attendee missing rating', () => {
    patchDashboardMock({
      nextMeeting: {
        id: 'october-42',
        status: 'started',
      },
      lifecycle: {
        missingRatings: [{ id: 1 }],
      },
    })

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).toContain('Поставь оценку книге')
    expect(wrapper.text()).toContain(
      'Встреча уже началась. Чтобы завершить цикл, нужна твоя оценка.',
    )
  })

  it('hides book candidate banner on home page via hideOn', () => {
    route.value = { path: '/', name: 'home' }

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).not.toContain('Ожидает проверки')
  })

  it('shows book candidate banner on non-home page', () => {
    route.value = { path: '/members', name: 'members' }

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).toContain('Ожидает проверки')
  })
})
