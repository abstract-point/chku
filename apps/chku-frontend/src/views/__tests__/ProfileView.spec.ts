import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { nextTick } from 'vue'

import ProfileView from '../ProfileView.vue'
import { useClubStore } from '@/stores/club'

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
    expect(wrapper.text()).toContain('Дюна')
    expect(
      wrapper
        .findAllComponents(RouterLinkStub)
        .some((link) => link.props('to') === '/archive'),
    ).toBe(true)
  })

  it('shows the proposal action only for the current selector', async () => {
    setActivePinia(createPinia())
    const club = useClubStore()

    const wrapper = mountProfile()

    expect(wrapper.text()).toContain('Сейчас твоя очередь')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/propose-selection')

    club.currentSelectorName = 'Михаил'
    await nextTick()

    expect(wrapper.text()).not.toContain('Сейчас твоя очередь')
    expect(wrapper.text()).not.toContain('Предложить книгу')
  })
})
