import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import BannerSection from '../BannerSection.vue'
import { patchDashboardMock, resetDashboardMock, resetAuthSession, setAuthRoles } from '@/test/setup'

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

  it('shows meeting rating banner on non-meeting page for attendee missing rating', () => {
    route.value = { path: '/', name: 'home' }

    patchDashboardMock({
      nextAction: {
        type: 'rate_book',
        priority: 70,
        title: 'Поставь оценку книге',
        description: 'Встреча уже началась. Чтобы закрыть цикл, нужна твоя оценка.',
        actionUrl: '/meetings/october-42',
      },
    })

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).toContain('Поставь оценку книге')
    expect(wrapper.text()).toContain('Встреча уже началась. Чтобы закрыть цикл, нужна твоя оценка.')
    expect(wrapper.find('.app-banner--action').exists()).toBe(true)
  })

  it('hides meeting rating banner on meeting detail page', () => {
    patchDashboardMock({
      nextAction: {
        type: 'rate_book',
        priority: 70,
        title: 'Поставь оценку книге',
        description: 'Встреча уже началась. Чтобы закрыть цикл, нужна твоя оценка.',
        actionUrl: '/meetings/october-42',
      },
    })

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).not.toContain('Поставь оценку книге')
  })

  it('hides book candidate banner on home page via hideOn', () => {
    route.value = { path: '/', name: 'home' }

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).not.toContain('Ответь по книге-кандидату')
  })

  it('shows book candidate banner on non-home page', () => {
    route.value = { path: '/members', name: 'members' }

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).toContain('Ответь по книге-кандидату')
    expect(wrapper.find('.app-banner--action').exists()).toBe(true)
  })

  it('links progress action to the progress form anchor', () => {
    route.value = { path: '/members', name: 'members' }
    patchDashboardMock({
      nextAction: {
        type: 'update_progress',
        priority: 50,
        title: 'Обнови прогресс чтения',
        description: 'Отметь, на каком проценте книги ты сейчас.',
        actionUrl: '/?action=update-progress#reading-progress',
      },
    })

    const wrapper = mount(BannerSection)

    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe(
      '/?action=update-progress#reading-progress',
    )
  })

  it('shows system banners with system styling', () => {
    route.value = { path: '/members', name: 'members' }
    setAuthRoles(['admin'])
    patchDashboardMock({
      nextAction: {
        type: 'none',
        priority: 0,
        title: '',
        description: '',
        actionUrl: '/',
      },
    })

    const wrapper = mount(BannerSection)

    expect(wrapper.text()).toContain('Системное уведомление')
    expect(wrapper.text()).toContain('Включите 2FA')
    expect(wrapper.find('.app-banner--system').exists()).toBe(true)
  })
})
