import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import MembersView from '../MembersView.vue'

function mountMembers() {
  const pinia = createPinia()
  setActivePinia(pinia)

  return mount(MembersView, {
    global: {
      plugins: [pinia],
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('MembersView', () => {
  it('renders heading and member cards', () => {
    const wrapper = mountMembers()

    expect(wrapper.text()).toContain('Участники')
    expect(wrapper.text()).toContain('6 человек в клубе')
    expect(wrapper.text()).toContain('Елена Воронцова')
    expect(wrapper.text()).toContain('Михаил Корнев')
    expect(wrapper.text()).toContain('Анна Соколова')
    expect(wrapper.text()).toContain('Тимур Васильев')
    expect(wrapper.text()).toContain('Ольга Петрова')
    expect(wrapper.text()).toContain('Дмитрий Смирнов')
  })

  it('renders member stats', () => {
    const wrapper = mountMembers()

    expect(wrapper.text()).toContain('Прочитано')
    expect(wrapper.text()).toContain('Предложено')
    expect(wrapper.text()).toContain('Встреч')
  })

  it('links to member detail pages', () => {
    const wrapper = mountMembers()
    const links = wrapper.findAllComponents(RouterLinkStub)

    expect(links.some((link) => link.props('to') === '/members/1')).toBe(true)
    expect(links.some((link) => link.props('to') === '/members/2')).toBe(true)
  })

  it('marks inactive member cards as muted', () => {
    const wrapper = mountMembers()
    const links = wrapper.findAllComponents(RouterLinkStub)
    const inactiveCard = links.find((link) => link.text().includes('Ольга Петрова'))

    expect(inactiveCard?.classes()).toContain('member-card--inactive')
    expect(inactiveCard?.find('.badge--muted').exists()).toBe(true)
  })
})
