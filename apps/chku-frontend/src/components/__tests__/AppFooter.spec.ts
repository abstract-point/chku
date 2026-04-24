import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia } from 'pinia'

import AppFooter from '@/components/AppFooter.vue'

describe('AppFooter', () => {
  it('renders club name, copyright and navigation links', () => {
    const wrapper = mount(AppFooter, {
      global: {
        plugins: [createPinia()],
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.text()).toContain('Читальный клуб умничек')
    expect(wrapper.text()).toContain('©')
    expect(wrapper.text()).toContain('Дашборд')
    expect(wrapper.text()).toContain('Архив')
    expect(wrapper.text()).toContain('Профиль')

    expect(wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))).toEqual([
      '/',
      '/archive',
      '/profile',
    ])
  })
})
