import { describe, expect, it } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'

import MainLayout from '@/components/layouts/MainLayout.vue'

function createTestRouter() {
  return createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div>Содержимое страницы</div>' } },
      { path: '/members', name: 'members', component: { template: '<div>Участники</div>' } },
    ],
  })
}

describe('MainLayout', () => {
  it('renders book choice verification above non-dashboard page content', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const router = createTestRouter()

    await router.push('/members')

    const wrapper = mount(MainLayout, {
      global: {
        plugins: [pinia, router],
        stubs: {
          RouterLink: RouterLinkStub,
          RouterView: { template: '<main>Содержимое страницы</main>' },
        },
      },
    })

    expect(wrapper.text()).toContain('Ответь по книге-кандидату')
    expect(wrapper.text()).toContain('Клуб ждёт твой ответ: читал ли ты эту книгу раньше.')
    expect(wrapper.text()).toContain('Содержимое страницы')
    expect(wrapper.text()).toContain('Профиль')
    expect(wrapper.text()).not.toContain('Предложить книгу')
    expect(wrapper.find('#modal-portal').exists()).toBe(false)
    expect(wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))).toEqual([
      '/',
      '/',
      '/members',
      '/archive',
      '/',
      '/members',
      '/archive',
      '/profile',
      '/propose-selection',
      '/profile/settings',
      '/',
    ])
  })

  it('hides book choice verification on dashboard', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    const router = createTestRouter()

    await router.push('/')

    const wrapper = mount(MainLayout, {
      global: {
        plugins: [pinia, router],
        stubs: {
          RouterLink: RouterLinkStub,
          RouterView: { template: '<main>Содержимое страницы</main>' },
        },
      },
    })

    expect(wrapper.text()).not.toContain('Ответь по книге-кандидату')
    expect(wrapper.text()).toContain('Содержимое страницы')
  })
})
