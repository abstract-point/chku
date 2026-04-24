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
    const wrapper = mountArchiveBook()

    expect(wrapper.text()).toContain('Дюна')
    expect(wrapper.text()).toContain('Фрэнк Герберт')
    expect(wrapper.text()).toContain('Михаил')
    expect(wrapper.text()).toContain('8.6/10')
    expect(wrapper.text()).toContain('Отзывы клуба')
    expect(wrapper.text()).toContain('Обсуждение')
    expect(wrapper.text()).toContain('Можно ли читать историю Пола')
  })

  it('renders fallback for unknown book slug', () => {
    const wrapper = mountArchiveBook('unknown-book')

    expect(wrapper.text()).toContain('Книга не найдена')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toBe('/archive')
  })
})
