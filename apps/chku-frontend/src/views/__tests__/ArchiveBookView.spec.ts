import { describe, expect, it, vi } from 'vitest'
import { mount, RouterLinkStub } from '@vue/test-utils'
import { ref } from 'vue'

import ArchiveBookView from '../ArchiveBookView.vue'

const route = ref({
  params: {
    slug: 'duna',
  },
})

vi.mock('vue-router', () => ({
  RouterLink: RouterLinkStub,
  useRoute: () => route.value,
}))

function mountArchiveBook(slug = 'duna') {
  route.value = {
    params: {
      slug,
    },
  }

  return mount(ArchiveBookView, {
    global: {
      stubs: {
        RouterLink: RouterLinkStub,
      },
    },
  })
}

describe('ArchiveBookView', () => {
  it('renders archived book details, reviews and discussion', () => {
    const wrapper = mountArchiveBook('ten-istoriya')

    expect(wrapper.text()).toContain('Тайная история')
    expect(wrapper.text()).toContain('Донна Тартт')
    expect(wrapper.text()).toContain('Елена')
    expect(wrapper.text()).toContain('9.2/10')
    expect(wrapper.text()).toContain('Отзывы клуба')
    expect(wrapper.text()).toContain('Обсуждение')
    expect(wrapper.text()).toContain('Где проходит граница')
    expect(wrapper.text()).toContain('Встреча в архиве')
    expect(wrapper.findAllComponents(RouterLinkStub).some((link) => link.props('to') === '/meetings/1')).toBe(true)
  })

  it('renders fallback for unknown book slug', () => {
    const wrapper = mountArchiveBook('unknown-book')

    expect(wrapper.text()).toContain('Книга не найдена')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/archive')
  })
})
