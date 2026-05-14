import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'

import UserAvatar from '@/components/UserAvatar.vue'

describe('UserAvatar', () => {
  it('renders uploaded image when avatar url exists', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        name: 'Елена Воронцова',
        avatarUrl: '/api/members/1/avatar',
      },
    })

    expect(wrapper.find('img').attributes('src')).toBe('/api/members/1/avatar')
    expect(wrapper.text()).not.toContain('ЕВ')
  })

  it('builds fallback from every name word', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        name: 'Анна Мария де Соуза',
      },
    })

    expect(wrapper.text()).toContain('АМДС')
  })

  it('renders as a router link when target is provided', () => {
    const wrapper = mount(UserAvatar, {
      props: {
        name: 'Павел Иванов',
        to: '/members/1',
      },
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/members/1')
  })
})
