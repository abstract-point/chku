import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'

import BannerNextAction from '../BannerNextAction.vue'
import type { ApiNextAction } from '@/api/types'

function mountBanner(action: ApiNextAction) {
  return mount(BannerNextAction, {
    props: { action },
    global: { stubs: { RouterLink: RouterLinkStub } },
  })
}

describe('BannerNextAction', () => {
  it('renders action link for regular next action', () => {
    const wrapper = mountBanner({
      type: 'update_progress',
      priority: 50,
      title: 'Обнови прогресс чтения',
      description: 'Отметь, на каком проценте книги ты сейчас.',
      actionUrl: '/#reading-progress',
    })

    expect(wrapper.text()).toContain('Обнови прогресс чтения')
    expect(wrapper.findComponent(RouterLinkStub).exists()).toBe(true)
  })

  it('renders paused notice without action link', () => {
    const wrapper = mountBanner({
      type: 'paused',
      priority: 100,
      title: 'Твоё участие на паузе',
      description: 'Вернуть участие может администратор.',
      actionUrl: '/',
    })

    expect(wrapper.text()).toContain('Твоё участие на паузе')
    expect(wrapper.findComponent(RouterLinkStub).exists()).toBe(false)
  })
})
