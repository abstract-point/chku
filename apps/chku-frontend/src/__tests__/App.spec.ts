import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { members } from '@/data/members'
import { useAuthStore } from '@/stores/auth'

import App from '@/App.vue'

describe('App', () => {
  it('renders book choice verification above page content', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const auth = useAuthStore()
    auth.authData = {
      user: members[0]!,
      roles: ['member'],
      permissions: [],
      twoFactorEnabled: false,
    }

    const wrapper = mount(App, {
      global: {
        plugins: [pinia],
        stubs: {
          RouterLink: RouterLinkStub,
          RouterView: { template: '<main>Содержимое страницы</main>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Ожидает проверки: «Тайную историю»')
    expect(wrapper.text()).toContain('Елена предложила «Тайную историю» — Донна Тартт')
    expect(wrapper.text()).toContain('Содержимое страницы')
    expect(wrapper.text()).toContain('Профиль')
    expect(wrapper.text()).not.toContain('Предложить книгу')
    expect(wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))).toEqual([
      '/',
      '/',
      '/members',
      '/archive',
      '/',
      '/archive',
      '/profile',
    ])
  })
})
