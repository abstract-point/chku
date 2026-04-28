import { describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import MemberDetailView from '../MemberDetailView.vue'

const route = ref({
  params: {
    id: '1',
  },
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
}))

function mountMemberDetail(id = '1') {
  route.value = {
    params: {
      id,
    },
  }

  return mount(MemberDetailView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('MemberDetailView', () => {
  it('renders member profile and reading history', () => {
    const wrapper = mountMemberDetail('1')

    expect(wrapper.text()).toContain('Елена Воронцова')
    expect(wrapper.text()).toContain('Участник клуба с 2021')
    expect(wrapper.text()).toContain('Активен')
    expect(wrapper.text()).toContain('Прочитано')
    expect(wrapper.text()).toContain('Предложено')
    expect(wrapper.text()).toContain('Встреч')
    expect(wrapper.text()).toContain('История чтения')
    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Донна Тартт')
  })

  it('renders inactive member correctly', () => {
    const wrapper = mountMemberDetail('5')

    expect(wrapper.text()).toContain('Ольга Петрова')
    expect(wrapper.text()).toContain('Неактивен')
  })

  it('links back to members list', () => {
    const wrapper = mountMemberDetail('1')

    expect(
      wrapper.findAllComponents(RouterLinkStub).some((link) => link.props('to') === '/members'),
    ).toBe(true)
  })

  it('renders fallback for unknown member id', () => {
    const wrapper = mountMemberDetail('999')

    expect(wrapper.text()).toContain('Участник не найден')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/members')
  })
})
