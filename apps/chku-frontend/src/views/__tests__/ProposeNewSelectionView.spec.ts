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
    expect(wrapper.text()).toContain('Выберите книгу')
  })

  it('validates required fields before submitting', async () => {
    setActivePinia(createPinia())

    const wrapper = mountProposal()

    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Укажи название книги.')
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

    expect(wrapper.text()).not.toContain('Укажи название книги.')
  })
})
