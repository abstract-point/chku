import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import ProfileView from '../ProfileView.vue'

function mountProfile() {
  return mount(ProfileView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('ProfileView', () => {
  it('renders current member profile and reading history', () => {
    setActivePinia(createPinia())

    const wrapper = mountProfile()

    expect(wrapper.text()).toContain('Елена')
    expect(wrapper.text()).toContain('Участник клуба с 2021')
    expect(wrapper.text()).toContain('Прочитано')
    expect(wrapper.text()).toContain('Предложено')
    expect(wrapper.text()).toContain('История чтения')
    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Франкенштейн')
    expect(
      wrapper
        .findAllComponents(RouterLinkStub)
        .some((link) => link.props('to') === '/archive'),
    ).toBe(true)
  })

  it('shows the proposal action for the current selector', () => {
    setActivePinia(createPinia())

    const wrapper = mountProfile()

    expect(wrapper.text()).toContain('Сейчас твоя очередь')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/propose-selection')
  })
})
