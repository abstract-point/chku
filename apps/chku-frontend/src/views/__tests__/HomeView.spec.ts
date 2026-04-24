import { describe, expect, it } from 'vitest'
import { createPinia } from 'pinia'
import { mount } from '@vue/test-utils'

import HomeView from '../HomeView.vue'

describe('HomeView', () => {
  it('renders the club name from Pinia state', () => {
    const wrapper = mount(HomeView, {
      global: {
        plugins: [createPinia()],
      },
    })

    expect(wrapper.text()).toContain('Читальный клуб умничек')
    expect(wrapper.text()).toContain('ЧКУ')
  })
})
