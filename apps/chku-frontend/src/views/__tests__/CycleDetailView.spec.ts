import { describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import CycleDetailView from '../CycleDetailView.vue'

const route = ref({
  params: {
    cycleNumber: '40',
  },
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
  useRouter: () => ({
    replace: vi.fn<() => void>(),
  }),
}))

function mountCycle(cycleNumber = '40') {
  route.value = {
    params: {
      cycleNumber,
    },
  }

  return mount(CycleDetailView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('CycleDetailView', () => {
  it('renders completed cycle details, reviews and discussion', () => {
    const wrapper = mountCycle('41')

    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Донна Тартт')
    expect(wrapper.text()).toContain('Елена')
    expect(wrapper.text()).toContain('9.2/10')
    expect(wrapper.text()).toContain('Отзывы клуба')
    expect(wrapper.text()).toContain('Дискуссия')
    expect(wrapper.text()).toContain('Встреча в архиве')
    expect(
      wrapper.findAllComponents(RouterLinkStub).some((link) => link.props('to') === '/meetings/1'),
    ).toBe(true)
  })

  it('renders fallback for unknown cycle number', () => {
    const wrapper = mountCycle('999')

    expect(wrapper.text()).toContain('Цикл не найден')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/archive')
  })
})
