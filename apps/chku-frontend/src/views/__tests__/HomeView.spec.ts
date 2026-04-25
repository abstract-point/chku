import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'

import HomeView from '../HomeView.vue'

describe('HomeView', () => {
  it('renders the dashboard content', () => {
    const wrapper = mount(HomeView, {
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.text()).toContain('Текущий цикл')
    expect(wrapper.text()).toContain('Тень ветра')
    expect(wrapper.text()).toContain('Следующая встреча')
    expect(wrapper.text()).toContain('Очередь выбора')
    expect(wrapper.text()).toContain('Клубная сводка')
    expect(wrapper.text()).not.toContain('Проверка книги ожидает ответа')
    expect(wrapper.text()).not.toContain('Базовое Vue-приложение')
  })
})
