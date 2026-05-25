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
      '/members',
      '/archive',
      '/profile',
      '/propose-selection',
      '/profile/settings',
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

    expect(wrapper.text()).not.toContain('Ожидает проверки: «Тайную историю»')
    expect(wrapper.text()).toContain('Содержимое страницы')
  })
})
