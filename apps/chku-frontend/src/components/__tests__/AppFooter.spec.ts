import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'

import AppFooter from '@/components/AppFooter.vue'

describe('AppFooter', () => {
  it('renders club name, copyright and stats', () => {
    const wrapper = mount(AppFooter, {
      global: {
        plugins: [createPinia()],
      },
    })

    expect(wrapper.text()).toContain('Читальный клуб умничек')
    expect(wrapper.text()).toContain('©')
  })
})
