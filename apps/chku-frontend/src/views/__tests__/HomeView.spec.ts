import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { activeCandidate, patchDashboardMock, resetDashboardMock } from '@/test/setup'

import HomeView from '../HomeView.vue'

vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal<typeof import('vue-router')>()

  return {
    ...actual,
    useRoute: () => ({ query: {}, hash: '' }),
    useRouter: () => ({ push: vi.fn() }),
  }
})

describe('HomeView', () => {
  afterEach(() => {
    resetDashboardMock()
  })

  it('renders the dashboard content', () => {
    patchDashboardMock({
      activeCandidate: null,
    })

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
    expect(wrapper.text()).toContain('Золото')
    expect(wrapper.text()).not.toContain('Серебро')
    expect(wrapper.text()).not.toContain('Бронза')
    expect(wrapper.text()).toContain('Очередь выбора')
    expect(wrapper.text()).not.toContain('Проверка книги ожидает ответа')
    expect(wrapper.text()).not.toContain('Базовое Vue-приложение')
  })

  it('renders book selection state first when there is an active candidate', () => {
    patchDashboardMock({
      activeCandidate,
    })

    const wrapper = mount(HomeView, {
      global: {
        stubs: {
          RouterLink: RouterLinkStub,
        },
      },
    })

    expect(wrapper.text()).toContain('Выбор книги')
    expect(wrapper.text()).toContain('Тайную историю')
    expect(wrapper.text()).toContain('Донна Тартт')
    expect(wrapper.text()).toContain('Ответы клуба')
    expect(wrapper.text()).toContain('Ждём ответ')
    expect(wrapper.text()).toContain('Прочитаю')
    expect(wrapper.text()).toContain('Я читал(а)')
    expect(wrapper.text()).not.toContain('Текущий цикл')
    expect(wrapper.text()).not.toContain('Тень ветра')
    expect(wrapper.text()).not.toContain('Текущий цикл ещё не начат')
  })
})
