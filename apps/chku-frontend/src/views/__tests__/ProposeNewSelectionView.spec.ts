import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import ProposeNewSelectionView from '../ProposeNewSelectionView.vue'

function mountProposal() {
  return mount(ProposeNewSelectionView)
}

describe('ProposeNewSelectionView', () => {
  it('renders the personal book queue form', () => {
    setActivePinia(createPinia())

    const wrapper = mountProposal()

    expect(wrapper.text()).toContain('Моя очередь книг')
    expect(wrapper.text()).toContain('Добавить книгу')
    expect(wrapper.text()).toContain('Название книги')
    expect(wrapper.text()).toContain('Почему эта книга?')
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
    await wrapper
      .get('#book-reason')
      .setValue('Подойдёт для разговора о времени, вине и милосердии.')
    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Название книги*')
  })

  it('selects a cover without changing book fields', async () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    await wrapper.get('#book-title').setValue('Дюна')
    await wrapper.get('#book-author').setValue('Фрэнк Герберт')
    await wrapper.find('.book-cover-picker__cover').trigger('click')

    expect((wrapper.get('#book-title').element as HTMLInputElement).value).toBe('Дюна')
    expect((wrapper.get('#book-author').element as HTMLInputElement).value).toBe('Фрэнк Герберт')
  })

  it('shows edit buttons for queue items', () => {
    setActivePinia(createPinia())
    const wrapper = mountProposal()

    const editButtons = wrapper.findAll('[aria-label="Редактировать книгу"]')
    expect(editButtons.length).toBeGreaterThan(0)
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
