import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia } from 'pinia'

import App from '@/App.vue'

describe('App', () => {
  it('renders book choice verification above page content', () => {
    const wrapper = mount(App, {
      global: {
        plugins: [createPinia()],
        stubs: {
          RouterLink: RouterLinkStub,
          RouterView: { template: '<main>Содержимое страницы</main>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Проверка книги ожидает ответа')
    expect(wrapper.text()).toContain('Елена предложила «Тайную историю» — Донна Тартт')
    expect(wrapper.text()).toContain('Содержимое страницы')
    expect(wrapper.text()).toContain('Профиль')
    expect(wrapper.text()).not.toContain('Предложить книгу')
    expect(wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))).toEqual([
      '/',
      '/',
      '/archive',
      '/profile',
    ])
  })
})
