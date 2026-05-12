import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'

import App from '@/App.vue'

function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', component: { template: '<div>Содержимое страницы</div>' } },
      { path: '/members', component: { template: '<div>Участники</div>' } },
    ],
  })
}

describe('App', () => {
  it('renders the active route content', async () => {
    const router = createTestRouter()
    await router.push('/')

    const wrapper = mount(App, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.text()).toContain('Содержимое страницы')
  })

  it('does not render app chrome around public route content', async () => {
    const router = createTestRouter()

    await router.push('/members')

    const wrapper = mount(App, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.text()).not.toContain('Ожидает проверки: «Тайную историю»')
    expect(wrapper.text()).not.toContain('Профиль')
    expect(wrapper.text()).toContain('Участники')
  })
})
