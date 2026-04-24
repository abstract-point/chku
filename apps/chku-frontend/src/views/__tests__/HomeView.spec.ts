import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import HomeView from '../HomeView.vue'

describe('HomeView', () => {
  it('renders the dashboard content', () => {
    const wrapper = mount(HomeView)

    expect(wrapper.text()).toContain('Текущий цикл')
    expect(wrapper.text()).toContain('Тень ветра')
    expect(wrapper.text()).toContain('Ближайшая встреча')
    expect(wrapper.text()).toContain('Порядок выбора')
    expect(wrapper.text()).not.toContain('Проверка книги ожидает ответа')
    expect(wrapper.text()).not.toContain('Базовое Vue-приложение')
  })
})
