import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import ProposeNewSelectionView from '../ProposeNewSelectionView.vue'

function mockWideViewport() {
  Object.defineProperty(window, 'innerWidth', {
    writable: true,
    configurable: true,
    value: 1440,
  })
  window.matchMedia = vi
    .fn<(query: string) => MediaQueryList>()
    .mockImplementation((query: string) => ({
      matches: query === '(min-width: 1280px)',
      media: query,
      onchange: null,
      addListener: vi.fn<() => void>(),
      removeListener: vi.fn<() => void>(),
      addEventListener: vi.fn<() => void>(),
      removeEventListener: vi.fn<() => void>(),
      dispatchEvent: vi.fn<() => boolean>(() => true),
    }))
}

function mountProposal() {
  mockWideViewport()
  return mount(ProposeNewSelectionView)
}

describe('ProposeNewSelectionView', () => {
  it('renders the personal book queue form', () => {
    setActivePinia(createPinia())

    const wrapper = mountProposal()

    expect(wrapper.text()).toContain('Моя очередь книг')
    expect(wrapper.text()).toContain('Добавить книгу')
    expect(wrapper.text()).toContain('Название книги')
  })

  it('marks required fields with an asterisk', () => {
    setActivePinia(createPinia())

    const wrapper = mountProposal()

    expect(wrapper.text()).toContain('Название книги*')
    expect(wrapper.text()).toContain('Автор*')
  })

  it('adds a book queue item and keeps the user on the queue page', async () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    await wrapper.get('#book-title').setValue('Лавр')
    await wrapper.get('#book-author').setValue('Евгений Водолазкин')
    await wrapper
      .get('#book-description')
      .setValue('История странника и его попытки искупить прошлое.')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Название книги*')
  })

  it('shows edit buttons for queue items', () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    const editButtons = wrapper.findAll('[aria-label="Редактировать книгу"]')
    expect(editButtons.length).toBeGreaterThan(0)
  })

  it('displays book description in queue item', () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    const firstBook = wrapper.find('.proposal__book')
    const content = firstBook.find('.proposal__book-content')

    expect(content.exists()).toBe(true)
    expect(content.find('.proposal__book-meta').text()).toBe(
      'Документальный роман о выборе под давлением.',
    )
  })

  it('opens inline edit form when edit is clicked', async () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    const editButton = wrapper.find('[aria-label="Редактировать книгу"]')
    await editButton.trigger('click')

    expect(wrapper.text()).toContain('Сохранить')
    expect(wrapper.text()).toContain('Отмена')
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  it('hides promote button on the first queue item', () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    const books = wrapper.findAll('.proposal__book')
    expect(books.length).toBeGreaterThanOrEqual(2)

    expect(books[0]!.find('[aria-label="Сделать книгу кандидатом"]').exists()).toBe(false)
    expect(books[1]!.find('[aria-label="Сделать книгу кандидатом"]').exists()).toBe(true)
  })
})
